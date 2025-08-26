# J1939 SAE CAN Log Parser

This script parses J1939 CAN log files, decodes message IDs, interprets PGNs and SPNs, and outputs human-readable results.

## Usage

```sh
php sae.php <dumpfile>
```

- `<dumpfile>`: Path to the CAN log file to parse.

## Features

- Decodes J1939 message IDs and extracts PGN, source/destination addresses, and priority.
- Recognizes and names common PGNs (e.g., EEC1, TP.CM, Request).
- Decodes SPNs for supported PGNs using predefined definitions.
- Handles multi-frame transport protocol messages (TP.CM/TP.DT).
- Outputs parsed frames to both `stdout` and out.txt.

## Output

For each frame, the script prints:

- Line number and timestamp (if available)
- Message ID (hex/decimal), PGN (hex/decimal), PGN name
- Source Address (SA), Destination Address (DA), Priority
- Raw bytes
- Special info for requests and transport protocol frames
- Decoded SPNs with description, value, and unit

Example output:

```
Line 1: Time=2024-06-13 12:34:56 ID=18FEF100 (18FEF100=419431680) PGN=0xFEF1 (65265) (CCVS (Cruise Control/Vehicle Speed)) SA=0 DA=241 PRI=6
  Bytes: 00 1A 2B 3C 4D 5E 6F 70
  SPN 84 (Wheel-Based Vehicle Speed): 10.0 km/h
  SPN 597 (Brake Pedal Position): 0.0 %
```

## PGN and SPN Definitions

PGN and SPN definitions are hardcoded in the script for common engine, vehicle, and aftertreatment messages. Each SPN includes:

- Byte index and length
- Scale and offset
- Unit and description

## Functions

### `decodeJ1939Id($messageId)`

Decodes a 29-bit J1939 CAN ID into priority, PGN, source/destination addresses.

## File Output

Results are appended to out.txt in the current directory.

---
