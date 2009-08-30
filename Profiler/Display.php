<?php
  /**
   * Port of PHP Quick Profiler by Ryan Campbell
   * Original URL: http://particletree.com/features/php-quick-profiler
   */
  class Profiler_Display {
    /**
     * Holds a copy of the config passed into the display() method
     * @var array
     */
    private static $config = array();

    /**
     * Outputs the HTML, CSS and JavaScript that builds the console display
     * @param array $output A multi-dimensional array containing all the data needed to build the console
     * @param array $config A list of configuration options
     */
    public static function display($output, $config) {
      self::$config = $config;
      self::displayJavascript();

      $logCount = count($output['logs']['console']) - $output['queryTotals']['count'];
      $fileCount = count($output['files']);
      $memoryUsed = $output['memoryTotals']['used'];
      $queryCount = $output['queryTotals']['all'];
      $speedTotal = $output['speedTotals']['total'];

      echo '<div id="pqp-container" class="pQp hideDetails" style="display:none">';
      echo '<div id="pQp" class="console">';
      echo '<table id="pqp-metrics" cellspacing="0">';
      echo '<tr>';
      echo '<td id="console" class="tab" style="color: #588E13;">';
      echo '<var>' . $logCount . '</var>';
      echo '<h4>Console</h4>';
      echo '</td>';
      echo '<td id="speed" class="tab" style="color: #3769A0;">';
      echo '<var>' . $speedTotal . '</var>';
      echo '<h4>Load Time</h4>';
      echo '</td>';
      echo '<td id="queries" class="tab" style="color: #953FA1;">';
      echo '<var>' . $queryCount . ' Queries</var>';
      echo '<h4>Database</h4>';
      echo '</td>';
      echo '<td id="memory" class="tab" style="color: #D28C00;">';
      echo '<var>' . $memoryUsed . '</var>';
      echo '<h4>Memory Used</h4>';
      echo '</td>';
      echo '<td id="files" class="tab" style="color: #B72F09;">';
      echo '<var>' . $fileCount . ' Files</var>';
      echo '<h4>Included</h4>';
      echo '</td>';
      echo '</tr>';
      echo '</table>';

      // Start Console tab
      echo '<div id="pqp-console" class="pqp-box">';

      if($logCount ==  0) {
        echo '<h3>This panel has no log items.</h3>';
      } else {
        echo '<table class="side" cellspacing="0">';
        echo '<tr>';
        echo '<td class="alt1"><var>'.$output['logs']['logCount'].'</var><h4>Logs</h4></td>';
        echo '<td class="alt2"><var>'.$output['logs']['errorCount'].'</var> <h4>Errors</h4></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td class="alt3"><var>'.$output['logs']['memoryCount'].'</var> <h4>Memory</h4></td>';
        echo '<td class="alt4"><var>'.$output['logs']['speedCount'].'</var> <h4>Speed</h4></td>';
        echo '</tr>';
        echo '</table>';
        echo '<table class="main" cellspacing="0">';

        $class = '';
        foreach ($output['logs']['console'] as $log) {
          if (!isset($log['type']) || $log['type'] == 'query')
            continue;

          echo '<tr class="log-' . $log['type'] . '">
          <td class="type">' . $log['type'] . '</td>
          <td class="' . $class . '">';
          if($log['type'] == 'log') {
            echo '<div><pre>' . $log['data'] . '</pre></div>';
          } elseif($log['type'] == 'memory') {
            echo '<div><pre>' . $log['data'] . '</pre> <em>' . $log['dataType'].'</em>: ' . $log['name'] . ' </div>';
          } elseif($log['type'] == 'speed') {
            echo '<div><pre>' . $log['data'] . '</pre> <em>' . $log['name'] . '</em></div>';
          } elseif($log['type'] == 'error') {
            echo '<div><em>Line ' . $log['line'].'</em> : ' . $log['data'] . ' <pre>' . $log['file'] . '</pre></div>';
          }

          echo '</td></tr>';
          $class = ($class == '') ? 'alt' : '';
        }

        echo '</table>';
      }
      echo '</div>';

      // Start Load Time tab
      echo '<div id="pqp-speed" class="pqp-box">';
      if ($output['logs']['speedCount'] ==  0) {
        echo '<h3>This panel has no log items.</h3>';
      } else {
        echo '<table class="side" cellspacing="0">';
        echo '<tr><td><var>' . $output['speedTotals']['total'] . '</var><h4>Load Time</h4></td></tr>';
        echo '<tr><td class="alt"><var>' . $output['speedTotals']['allowed'] . '</var> <h4>Max Execution Time</h4></td></tr>';
        echo '</table>';
        echo '<table class="main" cellspacing="0">';

        $class = '';
        foreach ($output['logs']['console'] as $log) {
          if (isset($log['type']) && $log['type'] == 'speed') {
            echo '<tr class="log-' . $log['type'] . '"><td class="' . $class . '">';
            echo '<div><pre>' . $log['data'] . '</pre> <em>' . $log['name'] . '</em></div>';
            echo '</td></tr>';
            $class = ($class == '') ? 'alt' : '';
          }
        }

        echo '</table>';
      }
      echo '</div>';

      // Start Database tab
      echo '<div id="pqp-queries" class="pqp-box">';
      if ($output['queryTotals']['count'] ==  0) {
        echo '<h3>This panel has no log items.</h3>';
      } else {
        echo '<table class="side" cellspacing="0">';
        echo '<tr><td><var>' . $output['queryTotals']['count'] . '</var><h4>Total Queries</h4></td></tr>';
        echo '<tr><td class="alt"><var>' . $output['queryTotals']['time'] . '</var> <h4>Total Time</h4></td></tr>';
        echo '<tr><td><var>' . $output['queryTotals']['duplicates'] . '</var> <h4>Duplicates</h4></td></tr>';
        echo '<tr><td class="alt">';
        echo '<var>' . $output['queryTotals']['types']['select']['total'] . ' (' . $output['queryTotals']['types']['select']['percentage'] . '%)</var>';
        echo '<var>' . $output['queryTotals']['types']['select']['time'] . ' (' . $output['queryTotals']['types']['select']['time_percentage'] . '%)</var>';
        echo '<h4>Selects</h4>';
        echo '</td></tr>';
        echo '<tr><td>';
        echo '<var>' . $output['queryTotals']['types']['update']['total'] . ' (' . $output['queryTotals']['types']['update']['percentage'] . '%)</var>';
        echo '<var>' . $output['queryTotals']['types']['update']['time'] . ' (' . $output['queryTotals']['types']['update']['time_percentage'] . '%)</var>';
        echo '<h4>Updates</h4>';
        echo '</td></tr>';
        echo '<tr><td class="alt">';
        echo '<var>' . $output['queryTotals']['types']['insert']['total'] . ' (' . $output['queryTotals']['types']['insert']['percentage'] . '%)</var>';
        echo '<var>' . $output['queryTotals']['types']['insert']['time'] . ' (' . $output['queryTotals']['types']['insert']['time_percentage'] . '%)</var>';
        echo '<h4>Inserts</h4>';
        echo '</td></tr>';
        echo '<tr><td>';
        echo '<var>' . $output['queryTotals']['types']['delete']['total'] . ' (' . $output['queryTotals']['types']['delete']['percentage'] . '%)</var>';
        echo '<var>' . $output['queryTotals']['types']['delete']['time'] . ' (' . $output['queryTotals']['types']['delete']['time_percentage'] . '%)</var>';
        echo '<h4>Deletes</h4>';
        echo '</td></tr>';
        echo '</table>';
        echo '<table class="main" cellspacing="0">';

        $class = '';
        foreach ($output['queries'] as $query) {
          echo '<tr><td class="' . $class . '">' . $query['sql'];
          if ($query['duplicate']) {
            echo '<strong style="display: block; color: #B72F09;">** Duplicate **</strong>';
          }

          if (isset($query['explain']) && $query['explain']) {
            echo '<em>';
            echo 'Possible keys: <b>' . $query['explain']['possible_keys'] . '</b> &middot;';
            echo 'Key Used: <b>' . $query['explain']['key'] . '</b> &middot;';
            echo 'Type: <b>' . $query['explain']['type'] . '</b> &middot;';
            echo 'Rows: <b>' . $query['explain']['rows'] . '</b> &middot;';
            echo 'Speed: <b>' . $query['time'] . '</b>';
            echo '</em>';
          } else if (isset($query['time'])) {
            echo '<em>Speed: <b>' . $query['time'] . '</b></em>';
          }

          if (isset($query['profile']) && is_array($query['profile'])) {
            echo '<div class="query-profile"><h4>&#187; Show Query Profile</h4>';

            echo '<table style="display: none">';
            foreach ($query['profile'] as $line)
              echo '<tr><td><em>' . $line['Status'] . '</em></td><td>' . $line['Duration'] . '</td></tr>';
            echo '</table>';

            echo '</div>';
          }

          echo '</td></tr>';
          $class = ($class == '') ? 'alt' : '';
        }

        echo '</table>';
      }
      echo '</div>';

      // Start Memory tab
      echo '<div id="pqp-memory" class="pqp-box">';
      if ($output['logs']['memoryCount'] ==  0) {
        echo '<h3>This panel has no log items.</h3>';
      } else {
        echo '<table class="side" cellspacing="0">';
        echo '<tr><td><var>' . $output['memoryTotals']['used'] . '</var><h4>Used Memory</h4></td></tr>';
        echo '<tr><td class="alt"><var>' . $output['memoryTotals']['total'] . '</var> <h4>Total Available</h4></td></tr>';
        echo '</table>';
        echo '<table class="main" cellspacing="0">';

        $class = '';
        foreach ($output['logs']['console'] as $log) {
          if (isset($log['type']) && $log['type'] == 'memory') {
            echo '<tr class="log-' . $log['type'] . '">';
            echo '<td class="' . $class . '"><b>' . $log['data'] . '</b> <em>' . $log['dataType'] . '</em>: ' . $log['name'] . '</td>';
            echo '</tr>';
            $class = ($class == '') ? 'alt' : '';
          }
        }

        echo '</table>';
      }
      echo '</div>';

      // Start Files tab
      echo '<div id="pqp-files" class="pqp-box">';
      if ($output['fileTotals']['count'] ==  0) {
        echo '<h3>This panel has no log items.</h3>';
      } else {
        echo '<table class="side" cellspacing="0">';
        echo '<tr><td><var>' . $output['fileTotals']['count'] . '</var><h4>Total Files</h4></td></tr>';
        echo '<tr><td class="alt"><var>' . $output['fileTotals']['size'] . '</var> <h4>Total Size</h4></td></tr>';
        echo '<tr><td><var>' . $output['fileTotals']['largest'] . '</var> <h4>Largest</h4></td></tr>';
        echo '</table>';
        echo '<table class="main" cellspacing="0">';

        $class ='';
        foreach ($output['files'] as $file) {
          echo '<tr><td class="' . $class . '"><b>' . $file['size'] . '</b> ' . $file['name'] . '</td></tr>';
          $class = ($class == '') ? 'alt' : '';
        }

        echo '</table>';
      }
      echo '</div>';

      // Start Footer
      echo '<table id="pqp-footer" cellspacing="0">';
      echo '<tr>';
      echo '<td class="credit"><a href="http://github.com/steves/PHP-Profiler" target="_blank"><strong>PHP</strong>&nbsp;Profiler</a></td>';
      echo '<td class="actions">';
      echo '<a class="detailsToggle" href="#">Details</a>';
      echo '<a class="heightToggle" href="#">Toggle Height</a>';
      echo '</td>';
      echo '</tr>';
      echo '</table>';
      echo '</div></div>';
    }

    private static function displayJavascript() {
      $css_url = self::$config['css_url'];
      echo '<script type="text/javascript">
          var sheet = document.createElement("link");
          sheet.setAttribute("rel", "stylesheet");
          sheet.setAttribute("type", "text/css");
          sheet.setAttribute("href", "' . $css_url . '");
          document.getElementsByTagName("head")[0].appendChild(sheet);
        </script>';

      echo '<script type="text/javascript">' . file_get_contents(dirname(__FILE__) . '/profiler.js') . '</script>';
    }
  }