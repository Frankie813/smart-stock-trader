"""
Utility functions for the ML pipeline
"""

import json
import sys
import logging
from pathlib import Path
from typing import Dict, Any, Optional

import pandas as pd
import numpy as np
from sklearn.metrics import accuracy_score, precision_score, recall_score, f1_score, roc_auc_score

from config import DATA_DIR, LOG_LEVEL

# Configure logging
logging.basicConfig(
    level=getattr(logging, LOG_LEVEL),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[logging.StreamHandler(sys.stderr)]
)

logger = logging.getLogger(__name__)


def load_data_from_csv(symbol: str, filename: Optional[str] = None) -> pd.DataFrame:
    """
    Load stock data from CSV file

    Args:
        symbol: Stock symbol (e.g., 'AAPL')
        filename: Optional custom filename. Defaults to {symbol}.csv

    Returns:
        DataFrame with stock data

    Raises:
        FileNotFoundError: If CSV file doesn't exist
        ValueError: If CSV is empty or malformed
    """
    if filename is None:
        filename = f"{symbol}.csv"

    filepath = DATA_DIR / filename

    if not filepath.exists():
        raise FileNotFoundError(f"Data file not found: {filepath}")

    logger.info(f"Loading data from {filepath}")

    df = pd.read_csv(filepath)

    if df.empty:
        raise ValueError(f"CSV file is empty: {filepath}")

    # Convert date column to datetime
    if 'date' in df.columns:
        df['date'] = pd.to_datetime(df['date'])
        df = df.sort_values('date').reset_index(drop=True)

    logger.info(f"Loaded {len(df)} rows from {filepath}")

    return df


def calculate_metrics(y_true: np.ndarray, y_pred: np.ndarray, y_pred_proba: Optional[np.ndarray] = None) -> Dict[str, float]:
    """
    Calculate classification metrics

    Args:
        y_true: True labels
        y_pred: Predicted labels
        y_pred_proba: Predicted probabilities (optional, for AUC)

    Returns:
        Dictionary of metrics
    """
    metrics = {
        'accuracy': float(accuracy_score(y_true, y_pred)),
        'precision': float(precision_score(y_true, y_pred, zero_division=0)),
        'recall': float(recall_score(y_true, y_pred, zero_division=0)),
        'f1_score': float(f1_score(y_true, y_pred, zero_division=0)),
    }

    if y_pred_proba is not None:
        try:
            metrics['roc_auc'] = float(roc_auc_score(y_true, y_pred_proba))
        except ValueError:
            metrics['roc_auc'] = 0.0

    return metrics


def calculate_trading_metrics(trades_df: pd.DataFrame) -> Dict[str, float]:
    """
    Calculate trading performance metrics

    Args:
        trades_df: DataFrame with columns: profit_loss, was_correct

    Returns:
        Dictionary of trading metrics
    """
    if trades_df.empty:
        return {
            'total_trades': 0,
            'total_profit_loss': 0.0,
            'win_rate': 0.0,
            'avg_profit_per_trade': 0.0,
            'sharpe_ratio': 0.0,
            'max_drawdown': 0.0,
        }

    total_trades = len(trades_df)
    total_profit_loss = trades_df['profit_loss'].sum()
    # Win rate = % of PROFITABLE trades (not prediction accuracy)
    win_rate = (trades_df['profit_loss'] > 0).mean() * 100
    avg_profit_per_trade = trades_df['profit_loss'].mean()

    # Calculate Sharpe Ratio (annualized, assuming 252 trading days)
    # Use percentage returns, not dollar amounts
    returns = trades_df['profit_loss_pct'] / 100  # Convert to decimal
    if returns.std() != 0:
        sharpe_ratio = (returns.mean() / returns.std()) * np.sqrt(252)
    else:
        sharpe_ratio = 0.0

    # Calculate Maximum Drawdown (percentage-based)
    # Returns are already in decimal form (0.02 = 2%)
    cumulative_returns = returns.cumsum()
    running_max = cumulative_returns.cummax()
    drawdown = cumulative_returns - running_max
    max_drawdown = drawdown.min() * 100  # Convert to percentage

    # Calculate largest win and loss
    largest_win = trades_df['profit_loss'].max() if not trades_df.empty else 0.0
    largest_loss = trades_df['profit_loss'].min() if not trades_df.empty else 0.0

    return {
        'total_trades': int(total_trades),
        'total_profit_loss': float(total_profit_loss),
        'win_rate': float(win_rate),
        'avg_profit_per_trade': float(avg_profit_per_trade),
        'sharpe_ratio': float(sharpe_ratio),
        'max_drawdown': float(max_drawdown),
        'largest_win': float(largest_win),
        'largest_loss': float(largest_loss),
    }


def save_results(results: Dict[str, Any], output_file: Optional[str] = None):
    """
    Save results as JSON to stdout or file

    Args:
        results: Dictionary of results
        output_file: Optional file path. If None, prints to stdout
    """
    json_output = json.dumps(results, indent=2)

    if output_file:
        output_path = Path(output_file)
        output_path.parent.mkdir(parents=True, exist_ok=True)
        with open(output_path, 'w') as f:
            f.write(json_output)
        logger.info(f"Results saved to {output_path}")
    else:
        # Print to stdout for Laravel to capture
        print(json_output)


def validate_dataframe(df: pd.DataFrame, required_columns: list) -> bool:
    """
    Validate that DataFrame has required columns

    Args:
        df: DataFrame to validate
        required_columns: List of required column names

    Returns:
        True if valid

    Raises:
        ValueError: If validation fails
    """
    missing_columns = set(required_columns) - set(df.columns)

    if missing_columns:
        raise ValueError(f"Missing required columns: {missing_columns}")

    # Check for NaN values in required columns
    for col in required_columns:
        if df[col].isna().any():
            nan_count = df[col].isna().sum()
            logger.warning(f"Column '{col}' has {nan_count} NaN values")

    return True


def handle_error(error: Exception, error_type: str = "ERROR") -> Dict[str, Any]:
    """
    Handle errors and return JSON error response

    Args:
        error: Exception object
        error_type: Type of error

    Returns:
        Error dictionary
    """
    error_dict = {
        'success': False,
        'error': error_type,
        'message': str(error),
    }

    logger.error(f"{error_type}: {error}", exc_info=True)

    return error_dict
