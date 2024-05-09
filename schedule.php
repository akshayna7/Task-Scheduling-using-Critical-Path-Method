<?php
//Example case
$tasks = [
    [
        'id' => '1',
        'name' => 'A',
        'duration' => '2',
        'dependencies' => [],
        'relationship' => [],
        'offset' => [],
        'ES' => 0,
        'EF' => 0,
        'LS' => 0,
        'LF' => 0,
        'float' => 0,
        'isCritical' => false,
    ],
    [
        'id' => '2',
        'name' => 'B',
        'duration' => '4',
        'dependencies' => ['1'],
        'relationship' => ['sf'],
        'offset' => [0],
        'ES' => 0,
        'EF' => 0,
        'LS' => 0,
        'LF' => 0,
        'float' => 0,
        'isCritical' => false,
    ],
    [
        'id' => '3',
        'name' => 'C',
        'duration' => '10',
        'dependencies' => ['2'],
        'relationship' => ['sf'],
        'offset' => [0],
        'ES' => 0,
        'EF' => 0,
        'LS' => 0,
        'LF' => 0,
        'float' => 0,
        'isCritical' => false,
    ],
    [
        'id' => '4',
        'name' => 'D',
        'duration' => '1',
        'dependencies' => ['2'],
        'relationship' => ['sf'],
        'offset' => [0],
        'ES' => 0,
        'EF' => 0,
        'LS' => 0,
        'LF' => 0,
        'float' => 0,
        'isCritical' => false,
    ],
    [
        'id' => '5',
        'name' => 'E',
        'duration' => '7',
        'dependencies' => ['4'],
        'relationship' => ['sf'],
        'offset' => [0],
        'ES' => 0,
        'EF' => 0,
        'LS' => 0,
        'LF' => 0,
        'float' => 0,
        'isCritical' => false,
    ],
    [
        'id' => '6',
        'name' => 'F',
        'duration' => '15',
        'dependencies' => ['4'],
        'relationship' => ['sf'],
        'offset' => [0],
        'ES' => 0,
        'EF' => 0,
        'LS' => 0,
        'LF' => 0,
        'float' => 0,
        'isCritical' => false,
    ]
];


$taskIdToIndex = [];
$maxPS = 0;
foreach ($tasks as $index => $task) {
    $tasks[$index]['successors']=[];
    $taskIdToIndex[$task['id']] = $index;
}

// FORWARD PASS
foreach ($tasks as $key => &$task) {
    if (empty($task['dependencies'])) {
        $task['ES'] = 1;
        $task['EF'] = $task['duration'];
    } else {
        $maxEF = 0;
        foreach ($task['dependencies'] as $k => $dependencyId) {
            $relationship = $task['relationship'][$k];
            $offset = $task['offset'][$k];
            $dependency = $tasks[$dependencyId - 1];

            switch ($relationship) {
                case 'ss': // Start-to-Start
                    $maxEF = max($maxEF, $dependency['ES'] + $offset);
                    break;
                case 'sf': // Start-to-Finish
                    $maxEF = max($maxEF, $dependency['EF'] + $offset);
                    break;
                case 'ff': // Finish-to-Finish
                    $maxEF = max($maxEF, $dependency['LF'] + $offset);
                    break;
                case 'fs': // Finish-to-Start
                    $maxEF = max($maxEF, $dependency['LS'] + $offset);
                    break;
            }

            //$maxEF = max($maxEF, $dependency['EF']);
            $tasks[$taskIdToIndex[$dependencyId]]['successors'][]=$task['id'];
        }
        $task['ES'] = $maxEF + 1;
        $task['EF'] = $task['ES'] + $task['duration'] - 1;
    }
    $maxPS = max($maxPS,$task['EF']);
}


// BACKWARD PASS
$lastTaskIndex = count($tasks) - 1;
$lastTask = &$tasks[$lastTaskIndex];
$lastTask['LF'] = $lastTask['EF'];
$lastTask['LS'] = $lastTask['ES']; // Set LS based on ES for the last task

for ($i = $lastTaskIndex; $i >= 0; $i--) {
  $task = &$tasks[$i];
  if (empty($task['successors'])) {
    $task['LF'] = $maxPS;
    $task['LS'] = $task['LF'] - $task['duration'] + 1;
  } else {
    $minEF = PHP_INT_MAX;
    foreach ($task['successors'] as $k => $successorId) {
      $successor = $tasks[$taskIdToIndex[$successorId]];

      //$relationship = $successor['relationship'][$k];
      //$offset = $successor['offset'][$k];

      // switch ($relationship) {
      //           case 'ss': // Start-to-Start
      //               $minEF = min($minEF, $successor['LS'] + $offset);
      //               break;
      //           case 'sf': // Start-to-Finish
      //               $minEF = min($minEF, $successor['EF'] + $offset);
      //               break;
      //           case 'ff': // Finish-to-Finish
      //               $minEF = min($minEF, $successor['LF'] + $offset);
      //               break;
      //           case 'fs': // Finish-to-Start
      //               $minEF = min($minEF, $successor['LS'] + $offset);
      //               break;
      //   }
      $minEF = min($minEF, $successor['LS']); // Use LF of predecessors
    }
    $task['LF'] = $minEF - 1; // Directly set LS based on minEF
    $task['LS'] = $task['LF'] - $task['duration'] + 1;
  }
  $task['float'] = $task['LF'] - $task['EF'];
  $task['isCritical'] = ($task['float'] == 0); // Adjust epsilon if needed
}

// Set the last task as critical if its float is 0
//$lastTask['isCritical'] = ($task['float'] == 0);

// Printing
echo "task id, task name, duration, ES, EF, LS, LF, float, isCritical\n";
foreach ($tasks as $task) {
    $isCritical = $task['isCritical'] ? 'True' : 'False';
    echo "{$task['id']}, {$task['name']}, {$task['duration']}, {$task['ES']}, {$task['EF']}, {$task['LS']}, {$task['LF']}, {$task['float']}, {$isCritical}\n";
}








