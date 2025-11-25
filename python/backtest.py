#!/usr/bin/env python3
"""
Backtest trading strategy using trained model

Usage: python backtest.py AAPL
"""

import sys
import json
import logging
from datetime import datetime
from pathlib import Path

import pandas as pd
import numpy as np
import joblib

import config
from feature_engineering import engineer_features, get_feature_list
from utils import (
    logger,
    load_data_from_csv,
    calculate_metrics,
    calculate_trading_metrics,
    save_results,
    handle_error
)

# Configure logging
logger = logging.getLogger(__name__)


def load_model(symbol: str):
    """
    Load trained model for a stock

    Args:
        symbol: Stock symbol

    Returns:
        Tuple of (model, metadata)

    Raises:
        FileNotFoundError: If model file doesn't exist
    """
    model_filename = f"{symbol}_model.pkl"
    model_path = config.MODELS_DIR / model_filename
    metadata_path = config.MODELS_DIR / f"{symbol}_metadata.json"

    if not model_path.exists():
        raise FileNotFoundError(
            f"Model not found: {model_path}. Please train the model first."
        )

    logger.info(f"Loading model from {model_path}")

    # Load the model
    model = joblib.load(model_path)

    # Load metadata if it exists
    metadata = {}
    if metadata_path.exists():
        with open(metadata_path, 'r') as f:
            metadata = json.load(f)
        logger.info(f"Model loaded. Version: {metadata.get('model_version', 'unknown')}")
        logger.info(f"Trained at: {metadata.get('trained_at', 'unknown')}")
    else:
        logger.warning(f"Metadata file not found: {metadata_path}")

    return model, metadata


def prepare_backtest_data(symbol: str, metadata: dict, config: dict):
    """
    Load and prepare data for backtesting

    Args:
        symbol: Stock symbol
        metadata: Model metadata dictionary
        config: Feature engineering configuration

    Returns:
        Tuple of (df_features, X, y, feature_names)
    """
    logger.info(f"Preparing backtest data for {symbol}")

    # Load raw data
    df = load_data_from_csv(symbol)

    # Engineer features (same as training)
    df_features = engineer_features(df, config)

    # Get feature columns (must match training features)
    feature_names = metadata.get('features_used', get_feature_list(df_features))

    # Prepare features and target
    X = df_features[feature_names].values
    y = df_features['target'].values

    logger.info(f"Backtest data prepared: {X.shape[0]} samples, {X.shape[1]} features")

    return df_features, X, y, feature_names


def simulate_trading(df_features: pd.DataFrame, predictions: np.ndarray,
                     prediction_probas: np.ndarray, initial_capital: float = 10000.0):
    """
    Simulate trading based on model predictions

    Trading Strategy:
    - If predict UP (1): Buy at open, sell at close
    - If predict DOWN (0): Stay in cash (no trade)

    Args:
        df_features: DataFrame with features and price data
        predictions: Model predictions (0 or 1)
        prediction_probas: Prediction probabilities
        initial_capital: Starting capital in dollars

    Returns:
        DataFrame with trade details
    """
    logger.info("Simulating trading...")

    trades = []
    capital = initial_capital

    for i, (idx, row) in enumerate(df_features.iterrows()):
        prediction = predictions[i]
        confidence = prediction_probas[i]
        actual = row['target']

        # Only trade if we predict UP
        if prediction == 1:
            entry_price = row['open']
            exit_price = row['close']

            # Realistic position sizing: buy as many shares as capital allows
            shares = int(capital // entry_price)  # Floor division for whole shares

            if shares > 0:  # Only trade if we can afford at least 1 share
                # Calculate profit/loss based on actual position size
                profit_loss = (exit_price - entry_price) * shares
                profit_loss_pct = ((exit_price - entry_price) / entry_price) * 100

                # Determine if prediction was correct
                was_correct = (prediction == actual)

                # Update capital
                capital += profit_loss

                trades.append({
                    'date': row['date'] if 'date' in row else None,
                    'prediction': int(prediction),
                    'actual': int(actual),
                    'confidence': float(confidence),
                    'entry_price': float(entry_price),
                    'exit_price': float(exit_price),
                    'profit_loss': float(profit_loss),
                    'profit_loss_pct': float(profit_loss_pct),
                    'was_correct': bool(was_correct),
                    'capital': float(capital),
                })

    trades_df = pd.DataFrame(trades)

    logger.info(f"Simulated {len(trades_df)} trades")

    return trades_df


def calculate_backtest_metrics(trades_df: pd.DataFrame, y_true: np.ndarray,
                                y_pred: np.ndarray, y_pred_proba: np.ndarray,
                                initial_capital: float = 10000.0):
    """
    Calculate comprehensive backtest metrics

    Args:
        trades_df: DataFrame with trade details
        y_true: True labels
        y_pred: Predicted labels
        y_pred_proba: Prediction probabilities
        initial_capital: Initial capital

    Returns:
        Dictionary of metrics
    """
    logger.info("Calculating backtest metrics...")

    # Prediction accuracy metrics
    prediction_metrics = calculate_metrics(y_true, y_pred, y_pred_proba)

    # Trading performance metrics
    if not trades_df.empty:
        trading_metrics = calculate_trading_metrics(trades_df)

        # Additional metrics
        final_capital = trades_df['capital'].iloc[-1] if len(trades_df) > 0 else initial_capital
        total_return = ((final_capital - initial_capital) / initial_capital) * 100
        total_return_dollars = final_capital - initial_capital

        # Winning and losing trade stats
        winning_trades = trades_df[trades_df['profit_loss'] > 0]
        losing_trades = trades_df[trades_df['profit_loss'] < 0]

        avg_win = winning_trades['profit_loss'].mean() if len(winning_trades) > 0 else 0.0
        avg_loss = losing_trades['profit_loss'].mean() if len(losing_trades) > 0 else 0.0

        # Profit factor
        gross_profit = winning_trades['profit_loss'].sum() if len(winning_trades) > 0 else 0.0
        gross_loss = abs(losing_trades['profit_loss'].sum()) if len(losing_trades) > 0 else 0.0
        profit_factor = gross_profit / gross_loss if gross_loss > 0 else 0.0

        trading_metrics.update({
            'initial_capital': float(initial_capital),
            'final_capital': float(final_capital),
            'total_return_pct': float(total_return),
            'total_return_dollars': float(total_return_dollars),
            'winning_trades': int(len(winning_trades)),
            'losing_trades': int(len(losing_trades)),
            'avg_win': float(avg_win),
            'avg_loss': float(avg_loss),
            'gross_profit': float(gross_profit),
            'gross_loss': float(gross_loss),
            'profit_factor': float(profit_factor),
        })
    else:
        trading_metrics = {
            'total_trades': 0,
            'initial_capital': float(initial_capital),
            'final_capital': float(initial_capital),
            'total_return_pct': 0.0,
        }

    # Log key metrics
    logger.info(f"Prediction Accuracy: {prediction_metrics['accuracy']:.4f}")
    logger.info(f"Total Trades: {trading_metrics['total_trades']}")
    logger.info(f"Win Rate: {trading_metrics.get('win_rate', 0):.2f}%")
    logger.info(f"Total Return: {trading_metrics.get('total_return_pct', 0):.2f}%")
    logger.info(f"Sharpe Ratio: {trading_metrics.get('sharpe_ratio', 0):.4f}")

    return {
        'prediction_metrics': prediction_metrics,
        'trading_metrics': trading_metrics,
    }


def main(symbol: str, initial_capital: float = 10000.0):
    """
    Main backtesting pipeline

    Args:
        symbol: Stock symbol
        initial_capital: Starting capital for simulation

    Returns:
        Dictionary of backtest results
    """
    try:
        # Load trained model
        model, metadata = load_model(symbol)

        # Reconstruct config from metadata (needed for feature engineering)
        config = {
            'name': metadata.get('model_version', 'Unknown'),
            'hyperparameters': metadata.get('hyperparameters', {}),
            'features_enabled': metadata.get('features_enabled', {}),
            'target_type': metadata.get('target_type', 'open_to_close')
        }

        # Prepare backtest data
        df_features, X, y, feature_names = prepare_backtest_data(symbol, metadata, config)

        # Make predictions
        logger.info("Making predictions...")
        predictions = model.predict(X)
        prediction_probas = model.predict_proba(X)[:, 1]

        # Simulate trading
        trades_df = simulate_trading(df_features, predictions, prediction_probas, initial_capital)

        # Calculate metrics
        metrics = calculate_backtest_metrics(
            trades_df, y, predictions, prediction_probas, initial_capital
        )

        # Prepare trade details for output (limit to last 100 trades)
        trades_list = []
        if not trades_df.empty:
            recent_trades = trades_df.tail(100)
            for _, trade in recent_trades.iterrows():
                trade_dict = trade.to_dict()
                # Convert date to string if present
                if 'date' in trade_dict and pd.notna(trade_dict['date']):
                    trade_dict['date'] = str(trade_dict['date'])
                trades_list.append(trade_dict)

        # Prepare results
        result = {
            'success': True,
            'symbol': symbol,
            'stock_symbol': symbol,
            'backtested_at': datetime.now().isoformat(),
            'model_version': metadata.get('model_version', 'unknown'),
            'model_trained_at': metadata.get('trained_at', 'unknown'),
            'data_summary': {
                'total_samples': int(len(df_features)),
                'num_features': int(len(feature_names)),
            },
            'prediction_metrics': metrics['prediction_metrics'],
            'trading_metrics': metrics['trading_metrics'],
            'recent_trades': trades_list,
        }

        logger.info("Backtesting complete")

        return result

    except Exception as e:
        return handle_error(e, "BACKTEST_ERROR")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python backtest.py <SYMBOL> [INITIAL_CAPITAL]")
        print("Example: python backtest.py AAPL 10000")
        sys.exit(1)

    symbol = sys.argv[1].upper()
    initial_capital = float(sys.argv[2]) if len(sys.argv) > 2 else 10000.0

    # Run backtest
    result = main(symbol, initial_capital)

    # Output results as JSON
    save_results(result)

    # Exit with appropriate code
    sys.exit(0 if result.get('success', False) else 1)
