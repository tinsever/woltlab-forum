"""Combines all phrase tables."""
from phrases_board import PHRASES as BOARD
from phrases_extras import PHRASES as EXTRAS
from phrases_integration import PHRASES as INTEGRATION
from phrases_permissions import PERMISSIONS, PERMISSION_CATEGORIES  # noqa: F401
from phrases_thread import PHRASES as THREAD

PHRASES = {}
for table in (BOARD, THREAD, INTEGRATION, EXTRAS):
    duplicates = set(PHRASES) & set(table)
    if duplicates:
        raise ValueError(f'duplicate phrases: {duplicates}')
    PHRASES.update(table)
