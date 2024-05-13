<?php
//Example input
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
        'relationship' => ['ss'],
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
        'dependencies' => ['1'],
        'relationship' => ['ss'],
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
        'dependencies' => ['2','3'],
        'relationship' => ['ss','ss'],
        'offset' => [0,0],
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
    $tasks[$index]['ff']=0;
    $taskIdToIndex[$task['id']] = $index;
}

// FORWARD PASS
foreach ($tasks as $key => &$task) {

    if (empty($task['dependencies'])) {
        $task['ES'] = 0;
        $task['EF'] = $task['duration'];
    } else {
        $maxEF = 0;
        $maxES = 0;
        foreach ($task['dependencies'] as $k => $dependencyId) {
            $relationship = $task['relationship'][$k];
            $offset = $task['offset'][$k];
            $dependency = $tasks[$dependencyId - 1];

            switch ($relationship) {
                case 'ss': // Start-to-Start
                    $maxES = max($maxES, $dependency['ES'] + $offset);
                    $task['ES'] = $maxES;
                    $task['EF'] = $maxEF = $task['ES'] + $task['duration'];
                    break;
                case 'sf': // Start-to-Finish
                    $maxEF = max($maxEF, $dependency['ES'] + $offset);
                    $task['EF'] = $maxEF;
                    $task['ES'] = $maxES = $task['EF'] - $task['duration'];
                    break;
                case 'ff': // Finish-to-Finish
                    $maxEF = max($maxEF, $dependency['EF'] + $offset);
                    $task['EF'] = $maxEF;
                    $task['ES'] = $maxES = $task['EF'] - $task['duration'];

                    break;
                case 'fs': // Finish-to-Start
                    $maxES = max($maxES, $dependency['EF'] + $offset);
                    $task['ES'] = $maxES;
                    $task['EF'] = $maxEF = $task['ES'] + $task['duration'];
                    break;
            }

            //$maxEF = max($maxEF, $dependency['EF']);
            $tasks[$taskIdToIndex[$dependencyId]]['successors'][]= array('id'=>$task['id'],'key'=>$k);
        }
    }

    $maxPS = max($maxPS,$task['EF']);
}





// BACKWARD PASS
$lastTaskIndex = count($tasks) - 1;
$lastTask = &$tasks[$lastTaskIndex];
$lastTask['LF'] = $maxPS;
$lastTask['LS'] = $lastTask['ES']; // Set LS based on ES for the last task
$task['float'] = $lastTask['LF'] - $lastTask['EF'];
$task['isCritical'] = ((int)$task['float'] == 0); 
for ($i = $lastTaskIndex; $i >= 0; $i--) {

  $task = &$tasks[$i];
  if (empty($task['successors'])) {

    $task['LF'] = $maxPS;
    $task['LS'] = $task['LF'] - $task['duration'];
    $task['float'] = $task['LS'] - $task['ES'];
  // echo print_r($task);
  } else {
    $minLF = PHP_INT_MAX;
    $minLS = PHP_INT_MAX;
    $maxES = PHP_INT_MAX;
    $maxEF = PHP_INT_MAX;
    foreach ($task['successors'] as $k => $successorData) {
      $successorId = $successorData['id']; 
      $keyIndex = $successorData['key']; 
      $successor = $tasks[$taskIdToIndex[$successorId]];
      $relationship = $successor['relationship'][$keyIndex];
      $offset = $successor['offset'][$keyIndex];

      switch ($relationship) {
                 case 'ss': // Start-to-Start
                    $minLF = min($minLF, $successor['LS'] - $offset);
                    $maxES = min($maxES,$successor['ES'] - $offset);
                    $task['LF'] = $minLF ;
                    $task['LS'] = $minLS = $task['LF'] - $task['duration'];
                    $task['float'] = $maxES - $task['EF'];

                    break;
                case 'sf': // Start-to-Finish
                    $minLS = min($minLF, $successor['LF'] - $offset);
                    $task['LS'] = $minLS ;
                    $task['LF'] = $minLF = $task['LS'] - $task['duration'];
                    $task['float'] = $task['LS'] - $task['ES'];
                    break;
                case 'ff': // Finish-to-Finish
                    $minLF = min($minLF, $successor['LF'] - $offset);
                    $maxEF = min($maxEF,$successor['EF'] - $offset);
                    $task['LF'] = $minLF;
                    $task['LS'] = $minLS = $task['LF'] - $task['duration'];
                    $task['float'] = $maxEF - $task['EF'];
                    break;
                case 'fs': // Finish-to-Start
                    $minLF = min($minLF, $successor['LS'] - $offset);
                    $task['LF'] = $minLF;
                    $task['LS'] = $minLS = $task['LF'] - $task['duration'];
                    $task['float'] = $task['LS'] - $task['ES'];
                    break;
        }
      //$minEF = min($minEF, $successor['LS']); // Use LF of predecessors
    }
  }
  $task['isCritical'] = ((int)$task['float'] <= 0); // Adjust epsilon if needed
 
}


// Printing
echo "task id, task name, duration, ES, EF, LS, LF, float, isCritical\n";
foreach ($tasks as $task) {
    $isCritical = $task['isCritical'] ? 'True' : 'False';
    echo "{$task['id']}, {$task['name']}, {$task['duration']}, {$task['ES']}, {$task['EF']}, {$task['LS']}, {$task['LF']}, {$task['float']}, {$isCritical}\n";
}

