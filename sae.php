<?php
if ($argc < 2) {
    echo "Usage: php {$argv[0]} <dumpfile>\n";
    exit(1);
}

$inputFile = $argv[1];
if (!is_readable($inputFile)) {
    echo "Cannot read file: $inputFile\n";
    exit(1);
}

$fileLines = file($inputFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$pgnNames = [
    59904 => 'Request (0xEA00)',
    60416 => 'Transport Protocol (TP.CM)',
    60160 => 'Transport Protocol (TP.DT)',
    60928 => 'Address Claimed (0xEE00)',
    61443 => 'EEC2 (Electronic Engine Controller 2)',
    61444 => 'EEC1 (Electronic Engine Controller 1)',
    61445 => 'EEC3 (Electronic Engine Controller 3)',
    65262 => 'ET1 (Engine Temperature 1)',
    65263 => 'PTO Information',
    65265 => 'CCVS (Cruise Control/Vehicle Speed)',
    65266 => 'LFE (Fuel Economy)',
    65270 => 'VD (Vehicle Distance)',
    65253 => 'HOURS (Engine Hours and Revolutions)',
    65257 => 'Engine Total Fuel Used',
    65271 => 'Cab Climate Control',
    65272 => 'Ambient Conditions',
    65273 => 'Inlet/Exhaust Conditions',
    65276 => 'Aftertreatment 1 Diesel Particulate Filter',
    65277 => 'Aftertreatment 2 Diesel Particulate Filter',
    65278 => 'Aftertreatment 1 SCR',
    65279 => 'Aftertreatment 2 SCR',
    65280 => 'Aftertreatment 1 Intake Gas',
    65281 => 'Aftertreatment 2 Intake Gas',
    65282 => 'Aftertreatment 1 Exhaust Gas',
    65283 => 'Aftertreatment 2 Exhaust Gas',
    65284 => 'Aftertreatment 1 Status',
    65285 => 'Aftertreatment 2 Status',
    65286 => 'Aftertreatment 1 Control',
    65287 => 'Aftertreatment 2 Control',
    65288 => 'Aftertreatment 1 Faults',
    65289 => 'Aftertreatment 2 Faults',
    65290 => 'Aftertreatment 1 Warnings',
    65291 => 'Aftertreatment 2 Warnings',
    65292 => 'Aftertreatment 1 Maintenance',
    65293 => 'Aftertreatment 2 Maintenance',
    65294 => 'Aftertreatment 1 Regeneration',
    65295 => 'Aftertreatment 2 Regeneration',
    65296 => 'Aftertreatment 1 Temperature',
    65297 => 'Aftertreatment 2 Temperature',
    65298 => 'Aftertreatment 1 Pressure',
    65299 => 'Aftertreatment 2 Pressure',
    65300 => 'Aftertreatment 1 Flow',
    65301 => 'Aftertreatment 2 Flow',
    65302 => 'Aftertreatment 1 Level',
    65303 => 'Aftertreatment 2 Level',
    65304 => 'Aftertreatment 1 Quality',
    65305 => 'Aftertreatment 2 Quality',
    65306 => 'Aftertreatment 1 Rate',
    65307 => 'Aftertreatment 2 Rate',
    65308 => 'Aftertreatment 1 Efficiency',
    65309 => 'Aftertreatment 2 Efficiency',
    65310 => 'Aftertreatment 1 Status 2',
    65311 => 'Aftertreatment 2 Status 2',
];

$spnDefinitions = [
    59904 => [
        0 => ['byteIndex'=>0,'length'=>3,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Requested PGN'],
    ],
    60416 => [
        0 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Control Byte'],
        1 => ['byteIndex'=>1,'length'=>2,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Total Message Size'],
        3 => ['byteIndex'=>3,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Number of Packets'],
        5 => ['byteIndex'=>5,'length'=>3,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Referenced PGN'],
    ],
    60160 => [
        0 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Sequence Number'],
        1 => ['byteIndex'=>1,'length'=>7,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Data Bytes'],
    ],
    60928 => [
        0 => ['byteIndex'=>0,'length'=>8,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'NAME Field'],
    ],
    61443 => [
        539 => ['byteIndex'=>1,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Accelerator Pedal Position 1'],
        91  => ['byteIndex'=>3,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Engine Percent Load at Current Speed'],
    ],
    61444 => [
        190 => ['byteIndex'=>3,'length'=>2,'scale'=>0.125,'offset'=>0,'unit'=>'rpm','description'=>'Engine Speed'],
        512 => ['byteIndex'=>1,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'%','description'=>'Actual Engine % Torque'],
        513 => ['byteIndex'=>2,'length'=>1,'scale'=>1,'offset'=>-125,'unit'=>'%','description'=>'Driver Demand % Torque'],
    ],
    61445 => [
        158 => ['byteIndex'=>0,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Engine Percent Torque'],
    ],
    65262 => [
        110 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Engine Coolant Temperature'],
        174 => ['byteIndex'=>1,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Fuel Temperature 1'],
        175 => ['byteIndex'=>2,'length'=>1,'scale'=>4,'offset'=>0,'unit'=>'kPa','description'=>'Engine Oil Pressure'],
    ],
    65263 => [
        976 => ['byteIndex'=>0,'length'=>2,'scale'=>0.125,'offset'=>0,'unit'=>'rpm','description'=>'PTO Speed'],
    ],
    65265 => [
        84  => ['byteIndex'=>1,'length'=>2,'scale'=>0.00390625,'offset'=>0,'unit'=>'km/h','description'=>'Wheel-Based Vehicle Speed'],
        597 => ['byteIndex'=>0,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Brake Pedal Position'],
    ],
    65266 => [
        183 => ['byteIndex'=>1,'length'=>2,'scale'=>0.05,'offset'=>0,'unit'=>'L/h','description'=>'Fuel Rate'],
        184 => ['byteIndex'=>3,'length'=>2,'scale'=>0.5,'offset'=>0,'unit'=>'L/100km','description'=>'Fuel Economy (Instantaneous)'],
    ],
    65270 => [
        161 => ['byteIndex'=>1,'length'=>2,'scale'=>0.00390625,'offset'=>0,'unit'=>'km/h','description'=>'Ground Speed'],
    ],
    65253 => [
        247 => ['byteIndex'=>0,'length'=>4,'scale'=>0.05,'offset'=>0,'unit'=>'h','description'=>'Engine Total Hours of Operation'],
        249 => ['byteIndex'=>4,'length'=>4,'scale'=>1000,'offset'=>0,'unit'=>'rev','description'=>'Total Engine Revolutions'],
    ],
    65257 => [
        182 => ['byteIndex'=>0,'length'=>4,'scale'=>0.5,'offset'=>0,'unit'=>'L','description'=>'Engine Total Fuel Used'],
    ],
    65271 => [
        171 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Cab Interior Temperature'],
    ],
    65272 => [
        108 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Ambient Air Temperature'],
    ],
    65273 => [
        105 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Intake Manifold Temperature'],
    ],
    65276 => [
        3701 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'DPF Soot Level'],
    ],
    65277 => [
        3702 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'DPF Soot Level'],
    ],
    65278 => [
        3703 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'SCR Conversion Efficiency'],
    ],
    65279 => [
        3704 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'SCR Conversion Efficiency'],
    ],
    65280 => [
        3705 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'°C','description'=>'Intake Gas Temperature'],
    ],
    65281 => [
        3706 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'°C','description'=>'Intake Gas Temperature'],
    ],
    65282 => [
        3707 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'°C','description'=>'Exhaust Gas Temperature'],
    ],
    65283 => [
        3708 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'°C','description'=>'Exhaust Gas Temperature'],
    ],
    65284 => [
        3709 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Status Byte'],
    ],
    65285 => [
        3710 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Status Byte'],
    ],
    65286 => [
        3711 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Control Byte'],
    ],
    65287 => [
        3712 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Control Byte'],
    ],
    65288 => [
        3713 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Fault Byte'],
    ],
    65289 => [
        3714 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Fault Byte'],
    ],
    65290 => [
        3715 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Warning Byte'],
    ],
    65291 => [
        3716 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Warning Byte'],
    ],
    65292 => [
        3717 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Maintenance Byte'],
    ],
    65293 => [
        3718 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Maintenance Byte'],
    ],
    65294 => [
        3719 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Regeneration Byte'],
    ],
    65295 => [
        3720 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Regeneration Byte'],
    ],
    65296 => [
        3721 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'°C','description'=>'Aftertreatment Temperature'],
    ],
    65297 => [
        3722 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'°C','description'=>'Aftertreatment Temperature'],
    ],
    65298 => [
        3723 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'kPa','description'=>'Aftertreatment Pressure'],
    ],
    65299 => [
        3724 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'kPa','description'=>'Aftertreatment Pressure'],
    ],
    65300 => [
        3725 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'L/min','description'=>'Aftertreatment Flow'],
    ],
    65301 => [
        3726 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'L/min','description'=>'Aftertreatment Flow'],
    ],
    65302 => [
        3727 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'Aftertreatment Level'],
    ],
    65303 => [
        3728 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'Aftertreatment Level'],
    ],
    65304 => [
        3729 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'Aftertreatment Quality'],
    ],
    65305 => [
        3730 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'Aftertreatment Quality'],
    ],
    65306 => [
        3731 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'L/h','description'=>'Aftertreatment Rate'],
    ],
    65307 => [
        3732 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'L/h','description'=>'Aftertreatment Rate'],
    ],
    65308 => [
        3733 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'Aftertreatment Efficiency'],
    ],
    65309 => [
        3734 => ['byteIndex'=>0,'length'=>2,'scale'=>0.1,'offset'=>0,'unit'=>'%','description'=>'Aftertreatment Efficiency'],
    ],
    65310 => [
        3735 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Status 2 Byte'],
    ],
    65311 => [
        3736 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'','description'=>'Status 2 Byte'],
    ],
];

$parsedFrames = [];
$multiFrameMessages = [];
$activeTransfers = [];

foreach ($fileLines as $lineNumber => $rawLine) {
    $trimmedLine = trim($rawLine);
    if ($trimmedLine === '') continue;

    if (preg_match('/\)\s+\S+\s+([0-9A-Fa-f]{3,8})#([0-9A-Fa-f]+)/', $trimmedLine, $matches)) {
        $messageIdHex = $matches[1];
        $messageDataHex = $matches[2];
    } else {
        continue;
    }

    $messageId = hexdec($messageIdHex);
    if (strlen($messageDataHex) % 2 !== 0) $messageDataHex = '0'.$messageDataHex;
    $messageBytes = [];
    for ($i=0; $i<strlen($messageDataHex); $i+=2) {
        $messageBytes[] = hexdec(substr($messageDataHex,$i,2));
    }

    $decodedId = decodeJ1939Id($messageId);
    $pgn = $decodedId['pgn'];
    $pgnHexString = sprintf("0x%05X",$pgn);

    $frame = [
        'raw_line'=>$trimmedLine,
        'id_hex'=>strtoupper($messageIdHex),
        'bytes'=>$messageBytes,
        'decoded'=>$decodedId,
        'pgn'=>$pgn,
        'pgn_hex'=>$pgnHexString,
        'pgn_name'=>$pgnNames[$pgn] ?? null,
        'lineno'=>$lineNumber+1,
    ];

    if ($pgn==59904 && count($messageBytes)>=3) {
        $requestedPgn = $messageBytes[0] | ($messageBytes[1]<<8) | ($messageBytes[2]<<16);
        $frame['is_request']=true;
        $frame['requested_pgn']=$requestedPgn;
        $frame['requested_pgn_hex']=sprintf("0x%05X",$requestedPgn);
    }

    if ($pgn==60416) {
        $frame['is_tp_cm']=true;
        $controlByte=$messageBytes[0]??null;
        $frame['tp_control']=$controlByte;
        $controlNames=[16=>'RTS',17=>'CTS',19=>'EndOfMsgACK',32=>'BAM',255=>'Abort'];
        $frame['tp_control_name']=$controlNames[$controlByte]??'Unknown';
        if (count($messageBytes)>=8) {
            $referencedPgn=$messageBytes[5]|($messageBytes[6]<<8)|($messageBytes[7]<<16);
            $frame['tp_referenced_pgn']=$referencedPgn;
        }
        $transferKey="{$decodedId['sa']}:{$decodedId['ps']}:{$frame['tp_referenced_pgn']}";
        $activeTransfers[$transferKey]=['cm_frame'=>$frame,'dt_frames'=>[]];
    } elseif ($pgn==60160) {
        $frame['is_tp_dt']=true;
        $frame['tp_seq']=$messageBytes[0]??null;
        foreach ($activeTransfers as $key=>&$transfer) {
            $cmFrame=$transfer['cm_frame']; $cmDecoded=$cmFrame['decoded'];
            if ($cmDecoded['sa']==$decodedId['sa']) {
                $transfer['dt_frames'][]=$frame;
                break;
            }
        } unset($transfer);
    }

    if (isset($spnDefinitions[$pgn])) {
        $decodedSpns=[];
        foreach ($spnDefinitions[$pgn] as $spn=>$definition) {
            $rawValue=0;
            for ($i=0;$i<$definition['length'];$i++) {
                $byteValue=$messageBytes[$definition['byteIndex']+$i]??0xFF;
                $rawValue |= $byteValue << (8*$i);
            }
            $notAvailable=(1<<(8*$definition['length']))-1;
            if ($rawValue==$notAvailable) {
                $value=null;
            } else {
                $value=$rawValue*$definition['scale']+$definition['offset'];
            }
            $decodedSpns[$spn]=['description'=>$definition['description'],'value'=>$value,'unit'=>$definition['unit']];
        }
        $frame['spns']=$decodedSpns;
    }

    $parsedFrames[]=$frame;
}

echo "=== J1939 Parse Results ===\n\n";
foreach ($parsedFrames as $frame) {
    $decoded = $frame['decoded'];
    $timestamp = null;
    if (preg_match('/^([0-9.]+)\)/', $frame['raw_line'], $tsMatch)) {
        $timestamp = $tsMatch[1];
        $tsFormatted = date('Y-m-d H:i:s', (int)$timestamp);
    } else {
        $tsFormatted = '';
    }

    $line = "Line {$frame['lineno']}:";
    if ($tsFormatted) $line .= " Time={$tsFormatted}";
    $line .= " ID={$frame['id_hex']} ({$frame['id_hex']}=".hexdec($frame['id_hex']).") PGN={$frame['pgn_hex']} ({$frame['pgn']})";
    if ($frame['pgn_name']) $line .= " ({$frame['pgn_name']})";
    $line .= " SA={$decoded['sa']} DA={$decoded['ps']} PRI={$decoded['priority']}";
    $output = $line . "\n";
    $output .= "  Bytes: " . implode(' ', array_map(fn($b) => sprintf("%02X", $b), $frame['bytes'])) . "\n";

    if (!empty($frame['is_request'])) {
        $output .= "  >> REQUEST for PGN {$frame['requested_pgn_hex']} ({$frame['requested_pgn']})\n";
    }
    if (!empty($frame['is_tp_cm'])) {
        $output .= "  >> TP.CM control={$frame['tp_control_name']}\n";
    }
    if (!empty($frame['is_tp_dt'])) {
        $output .= "  >> TP.DT seq={$frame['tp_seq']}\n";
    }
    if (!empty($frame['spns'])) {
        foreach ($frame['spns'] as $spn => $spnData) {
            $valueString = is_null($spnData['value']) ? "N/A" : $spnData['value'] . " " . $spnData['unit'];
            $output .= "  SPN $spn ({$spnData['description']}): $valueString\n";
        }
    }
    $output .= "\n";
    file_put_contents('out.txt', $output, FILE_APPEND);
    echo $output;
}

function decodeJ1939Id($messageId) {
    $priority=($messageId>>26)&0x7;
    $dataPage=($messageId>>24)&0x1;
    $pduFormat=($messageId>>16)&0xFF;
    $pduSpecific=($messageId>>8)&0xFF;
    $sourceAddress=$messageId&0xFF;
    if ($pduFormat>=240) {
        $pgn=($dataPage<<16)|($pduFormat<<8)|$pduSpecific;
    } else {
        $pgn=($dataPage<<16)|($pduFormat<<8);
    }
    return [
        'priority'=>$priority,
        'pf'=>$pduFormat,
        'ps'=>$pduSpecific,
        'sa'=>$sourceAddress,
        'pgn'=>$pgn
    ];
}
