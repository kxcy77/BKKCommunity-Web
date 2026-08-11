-- Repair the UTF-8 en dash values that were double-encoded during an early
-- production seed import. The hexadecimal form keeps this migration safe
-- regardless of the MySQL client's terminal encoding.
UPDATE local_services
SET opening_hours = REPLACE(
    opening_hours,
    CONVERT(0xC3A2E282ACE2809C USING utf8mb4),
    CONVERT(0xE28093 USING utf8mb4)
)
WHERE LOCATE(
    CONVERT(0xC3A2E282ACE2809C USING utf8mb4),
    opening_hours
) > 0;
