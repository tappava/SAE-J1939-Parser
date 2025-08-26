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
];

$spnDefinitions = [
    61444 => [
        190 => ['byteIndex'=>3,'length'=>2,'scale'=>0.125,'offset'=>0,'unit'=>'rpm','description'=>'Engine Speed'],
        512 => ['byteIndex'=>1,'length'=>1,'scale'=>1,'offset'=>0,'unit'=>'%','description'=>'Actual Engine % Torque'],
        513 => ['byteIndex'=>2,'length'=>1,'scale'=>1,'offset'=>-125,'unit'=>'%','description'=>'Driver Demand % Torque'],
    ],
    61443 => [
        539 => ['byteIndex'=>1,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Accelerator Pedal Position 1'],
        91  => ['byteIndex'=>3,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Engine Percent Load at Current Speed'],
    ],
    61445 => [
        158 => ['byteIndex'=>0,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Engine Percent Torque'],
    ],
    65265 => [
        84  => ['byteIndex'=>1,'length'=>2,'scale'=>0.00390625,'offset'=>0,'unit'=>'km/h','description'=>'Wheel-Based Vehicle Speed'],
        597 => ['byteIndex'=>0,'length'=>1,'scale'=>0.4,'offset'=>0,'unit'=>'%','description'=>'Brake Pedal Position'],
    ],
    65266 => [
        183 => ['byteIndex'=>1,'length'=>2,'scale'=>0.05,'offset'=>0,'unit'=>'L/h','description'=>'Fuel Rate'],
        184 => ['byteIndex'=>3,'length'=>2,'scale'=>0.5,'offset'=>0,'unit'=>'L/100km','description'=>'Fuel Economy (Instantaneous)'],
    ],
    65253 => [
        247 => ['byteIndex'=>0,'length'=>4,'scale'=>0.05,'offset'=>0,'unit'=>'h','description'=>'Engine Total Hours of Operation'],
        249 => ['byteIndex'=>4,'length'=>4,'scale'=>1000,'offset'=>0,'unit'=>'rev','description'=>'Total Engine Revolutions'],
    ],
    65262 => [
        110 => ['byteIndex'=>0,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Engine Coolant Temperature'],
        174 => ['byteIndex'=>1,'length'=>1,'scale'=>1,'offset'=>-40,'unit'=>'°C','description'=>'Fuel Temperature 1'],
        175 => ['byteIndex'=>2,'length'=>1,'scale'=>4,'offset'=>0,'unit'=>'kPa','description'=>'Engine Oil Pressure'],
    ],
    65263 => [
        976 => ['byteIndex'=>0,'length'=>2,'scale'=>0.125,'offset'=>0,'unit'=>'rpm','description'=>'PTO Speed'],
    ],
    65270 => [
        161 => ['byteIndex'=>1,'length'=>2,'scale'=>0.00390625,'offset'=>0,'unit'=>'km/h','description'=>'Ground Speed'],
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
    $decoded=$frame['decoded'];
    $line="Line {$frame['lineno']}: ID={$frame['id_hex']} PGN={$frame['pgn_hex']}";
    if ($frame['pgn_name']) $line.=" ({$frame['pgn_name']})";
    $line.=" SA={$decoded['sa']} DA={$decoded['ps']} PRI={$decoded['priority']}";
    $output = $line."\n";
    $output .= "  Bytes: ".implode(' ',array_map(fn($b)=>sprintf("%02X",$b),$frame['bytes']))."\n";

    if (!empty($frame['is_request'])) {
        $output .= "  >> REQUEST for PGN {$frame['requested_pgn_hex']}\n";
    }
    if (!empty($frame['is_tp_cm'])) {
        $output .= "  >> TP.CM control={$frame['tp_control_name']}\n";
    }
    if (!empty($frame['is_tp_dt'])) {
        $output .= "  >> TP.DT seq={$frame['tp_seq']}\n";
    }
    if (!empty($frame['spns'])) {
        foreach ($frame['spns'] as $spn=>$spnData) {
            $valueString=is_null($spnData['value'])?"N/A":$spnData['value']." ".$spnData['unit'];
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
