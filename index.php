<?php
include_once './core/stalker_configuration.core.php';

$database = Stalker_Configuration::database_connection();
$database = $database->database;
$backup_dir = './backups';
$password = "thisisapassword";

if(isset($_POST['passwordForm']) && isset($_POST['password'])) {

    header('Content-type: application/json');
    $response = array();

    if($_POST['password'] == $password) {
        $response["state"] = "ok";
        $response["backups"] = array();
        foreach ( glob($backup_dir."/*.sql") as $file ) {
            $explosion = explode('~', $file);
            $backup_database = $explosion[1];
            if($database == $backup_database){
                $backup_date = $explosion[2];
                $backup_series = explode('.', $explosion[3]);
                $backup_series = $backup_series[0];
                $backup_time = substr_replace($backup_series, ":", 2, 0);
                $backup_time = substr_replace($backup_time, ":", 5, 0);
                $response["backups"][] = array(
                    "database" => $backup_database,
                    "date" => $backup_date,
                    "series" => $backup_series,
                    "time" => $backup_time
                );
            }
        }
    } else {
        $response["state"] = "Wrong password";
    }
    echo json_encode($response);
    exit();
}

if(isset($_POST['createBackup']) && isset($_POST['password'])) {
    if($_POST['password'] == $password) {
        include_once './core/stalker_validator.core.php';
        include_once './core/stalker_database.core.php';
        include_once './core/stalker_information_schema.core.php';
        include_once './core/stalker_backup.core.php';

        Stalker_Backup::create_backup();
        $response = "ok";
    } else {
        $response = "Wrong password";
    }
    echo $response;
    exit();
}

if(isset($_POST['restoreBackup']) && isset($_POST['password']) && isset($_POST['series'])) {
    if($_POST['password'] == $password) {
        $explosion = explode('~', $_POST['series']);
        $backup_database = $explosion[0];
        if($database == $backup_database){
            $backup_date = $explosion[1];
            $backup_series = $explosion[2];
            include_once './core/stalker_validator.core.php';
            include_once './core/stalker_database.core.php';
            include_once './core/stalker_information_schema.core.php';
            include_once './core/stalker_backup.core.php';

            Stalker_Backup::restore_backup($backup_date, $backup_series);
            $response = "ok";
        } else {
            $response = "You can't restore a database other then the one specified in the configuration";
        }
    } else {
        $response = "Wrong password";
    }
    echo $response;
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Restore Database Backup</title>

    <link rel="stylesheet"
        href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css"
        integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u"
        crossorigin="anonymous">
</head>
<style>
    body {
        padding: 50px 0;
    }
    .content-container {
        display: none;
    }
    .table-container {
        margin-top: 20px
    }
</style>
<body>

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <form class="password-container" onsubmit="return false;">
                <div class="form-group">
                    <label for="passowrd">Password</label>
                    <input type="password" id="password" class="form-control">
                </div>
                    <input type="submit" class="btn btn-default">
                </form>
                <div class="content-container">
                    <div class="new-backup-container">
                        <a href="javascript:void(0)" class="create-backup btn btn-default">
                            + Make a new backup
                        </a>
                    </div>
                    <div class="table-container">
                        <table class="table" id="backups-table">
                            <thead>
                                <th>Database</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Options</th>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous">
    </script>
    <script>
        jQuery(document).ready(function ($){
            // login
            $("form.password-container").on("submit", function(){
                var password = $("#password").val();
                $.post("",
                {
                    passwordForm : true,
                    password : password
                },
                function(data, status){
                    if(status == 'success')
                    {
                        if(data.state == 'ok')
                        {
                            var template = '\
                                <tr><td>{database}</td><td>{date}</td><td>{time}</td>\
                                <td><a href="javascript:void(0)" class="restore-backup" data-series="{series}">\
                                    Restore Backup\
                                </a></td></tr>';
                            $("#backups-table > tbody").html("");
                            data.backups.reverse().forEach(function(backup){
                                var row = template.replace(new RegExp('{database}', 'g'), backup.database);
                                row = row.replace(new RegExp('{date}', 'g'), backup.date);
                                row = row.replace(new RegExp('{time}', 'g'), backup.time);
                                row = row.replace(new RegExp('{series}', 'g'), backup.database+"~"+backup.date+"~"+backup.series);
                                $("#backups-table > tbody").append(row);
                            })

                            $(".password-container").hide();
                            $(".content-container").show();
                        }else
                        {
                            alert(data.state);
                        }
                    } else
                    {
                        alert("An unknown error happened. please refresh the page");
                    }
                });
            });
            // create backup
            $(".create-backup").click(function(){
                var password = $("#password").val();
                $.post("",
                {
                    createBackup : true,
                    password : password
                },
                function(data, status){
                    if(status == 'success')
                    {
                        if(data == 'ok')
                        {
                            $("form.password-container").submit();
                        }else
                        {
                            alert(data);
                        }
                    } else
                    {
                        alert("An unknown error happened. please refresh the page");
                    }
                });
            });
            // restore backup
            $(document).on("click", ".restore-backup", function(){
                var password = $("#password").val(),
                    series = $(this).attr("data-series");
                $.post("",
                {
                    restoreBackup : true,
                    series : series,
                    password : password
                },
                function(data, status){
                    if(status == 'success')
                    {
                        if(data == 'ok')
                        {
                            alert("Database restored successfully");

                        }else
                        {
                            alert(data);
                        }
                    } else
                    {
                        alert("An unknown error happened. please refresh the page");
                    }
                });
            });

        });
    </script>
</body>
</html>
