<?php
/* CONFIGURATION */
//Connection to DB
    $db_connect = pg_connect("host=localhost port=5432 dbname=DATABASE_NAME user=USER_DATABASE password=PASSWORD_DATABASE options='--client_encoding=UTF8'");
    //IDs of users inside OpenPoject
    $worker = array('xx', 'xx', 'xx');
    //API_KEY of Toogl of each user 
    $api_key = array('API_user1', 'API_user2', 'API_user3');

    //Change the number 3 with total of workers
    for ($x = 0; $x <= 3; $x++) {
/* END CONFIGURATION */

    //Get Toogl Entries
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.toggl.com/api/v8/time_entries");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $api_key[$x] . ":api_token");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    $result = json_decode(curl_exec($ch));
    curl_close($ch);

    foreach($result as $row){
        if(!empty($row->description) && !empty($row->id) && !empty($row->pid)){
        $number_rows = pg_num_rows(pg_query($db_connect, "SELECT * FROM time_entries WHERE toggl_id='$row->id'"));
            if($number_rows == 0){
                //Search the Project and if we don´t find it whe add toggl project id to identifier
                $sql_pro_exist = pg_num_rows(pg_query($db_connect, "SELECT * FROM projects WHERE identifier LIKE '%{$row->pid}%'"));
                if($sql_pro_exist == 0){
                    $ch_project = curl_init();
                    curl_setopt($ch_project, CURLOPT_URL, "https://www.toggl.com/api/v8/projects/" . $row->pid);
                    curl_setopt($ch_project, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch_project, CURLOPT_USERPWD, $api_key[$x] . ":api_token");
                    curl_setopt($ch_project, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_project, CURLINFO_HEADER_OUT, true);

                    $result_project = json_decode(curl_exec($ch_project));
                    curl_close($ch_project);
                    sleep(2);

                    //Insert the project id of toogle inside identifier of Project inside OpenProject
                    foreach($result_project as $row_project){
                        $project_name = $row_project->name;
                        $id_project = $row_project->id;
                        $sql_pro = "UPDATE projects SET identifier = CONCAT(identifier, '-', '{$id_project}') WHERE name LIKE '%{$project_name}%' AND identifier NOT LIKE '%{$id_project}%'";
                        if (pg_query($db_connect, $sql_pro)) {
                            
                        } else {
                            echo "Error update ID Project: " . $sql_pro . "<br>" . pg_result_error($db_connect);
                        }
                    }

                    $sql_proyecto_open = pg_query($db_connect, "SELECT id FROM projects WHERE identifier LIKE '%{$row->pid}%'");
                    $row_id_pro = pg_fetch_assoc($sql_proyecto_open);
                    $project_id = $row_id_pro['id'];

                    if($project_id){
                        $sql_proyecto_modulo = pg_query($db_connect, "SELECT id FROM work_packages WHERE project_id = '{$project_id}' AND subject LIKE '%{$row->description}%'");
                        $row_id_modul = pg_fetch_assoc($sql_proyecto_modulo);
                        $work_package_id = $row_id_modul['id'];
                    }

                    if(!empty($project_id) && !empty($work_package_id)){
                        $arr = explode("T", $row->start);
                        $date_toggl_entrie = $arr[0];
                        $arr = explode("-", $date_toggl_entrie);
                        $ano = $arr[0];
                        $mes_con_cero = $arr[1];
                        $mes = (int)$mes_con_cero;
                        $week = date("W", strtotime($date_toggl_entrie));
                        $date_now = date("Y-m-d H:i:s"); 

                        $seconds = $row->duration;
                        $minutes = $row->duration/60;
                        $hours = round($minutes/60, 2);
                        $costs = '0.0000';
                
                        if ($hours > 0){
                            $sql = "INSERT INTO time_entries (project_id, user_id, work_package_id, hours, activity_id, spent_on, tyear, tmonth, tweek, created_on, updated_on, costs, toggl_id) VALUES ('{$project_id}', '{$worker[$x]}', '{$work_package_id}', '{$hours}', '1', '{$date_toggl_entrie}', '{$ano}', '{$mes}', '{$week}', '{$date_now}', '{$date_now}', '{$costs}', '{$row->id}')";
                            if (pg_query($db_connect, $sql)) {
                
                            } else {
                                echo "Error first time insert Id Project: " . $sql . "<br>" . pg_result_error($db_connect);
                            }
                        }
                    }
                    elseif (!empty($project_id) && empty($work_package_id)){
                        $sql_proyecto_modulo = pg_query($db_connect, "SELECT id FROM work_packages WHERE project_id = '{$project_id}' AND subject LIKE '%Others%'");
                        $row_id_modul = pg_fetch_assoc($sql_proyecto_modulo);
                        $work_package_id = $row_id_modul['id'];

                        if(!empty($work_package_id)){
                            $arr = explode("T", $row->start);
                            $date_toggl_entrie = $arr[0];
                            $arr = explode("-", $date_toggl_entrie);
                            $ano = $arr[0];
                            $mes_con_cero = $arr[1];
                            $mes = (int)$mes_con_cero;
                            $week = date("W", strtotime($date_toggl_entrie));
                            $date_now = date("Y-m-d H:i:s"); 

                            $seconds = $row->duration;
                            $minutes = $row->duration/60;
                            $hours = round($minutes/60, 2);
                            $costs = '0.0000';
                    
                            if ($hours > 0){
                                $sql = "INSERT INTO time_entries (project_id, user_id, work_package_id, hours, comments, activity_id, spent_on, tyear, tmonth, tweek, created_on, updated_on, costs, toggl_id) VALUES ('{$project_id}', '{$worker[$x]}', '{$work_package_id}', '{$hours}', '{$row->description}', '1', '{$date_toggl_entrie}', '{$ano}', '{$mes}', '{$week}', '{$date_now}', '{$date_now}', '{$costs}', '{$row->id}')";
                                if (pg_query($db_connect, $sql)) {
                    
                                } else {
                                    echo "Error first time insert Id Project: " . $sql . "<br>" . pg_result_error($db_connect);
                                }
                            }
                        }
                    }
                }
                else{
                    $sql_proyecto_open = pg_query($db_connect, "SELECT id FROM projects WHERE identifier LIKE '%{$row->pid}%'");
                    $row_id_pro=pg_fetch_assoc($sql_proyecto_open);
                    $project_id = $row_id_pro['id'];

                    if($project_id){
                        $sql_proyecto_modulo = pg_query($db_connect, "SELECT id FROM work_packages WHERE project_id = '{$project_id}' AND subject = '{$row->description}'");
                        $row_id_modul=pg_fetch_assoc($sql_proyecto_modulo);
                        $work_package_id = $row_id_modul['id'];
                    }
                    
                    if(!empty($project_id) && !empty($work_package_id)){
                        $arr = explode("T", $row->start);
                        $date_toggl_entrie = $arr[0];
                        $arr = explode("-", $date_toggl_entrie);
                        $ano = $arr[0];
                        $mes_con_cero = $arr[1];
                        $mes = (int)$mes_con_cero;
                        $week = date("W", strtotime($date_toggl_entrie));
                        $date_now = date("Y-m-d H:i:s"); 

                        $seconds = $row->duration;
                        $minutes = $row->duration/60;
                        $hours = round($minutes/60, 2);
                        $costs = '0.0000';
                        if ($hours > 0){
                            $sql = "INSERT INTO time_entries (project_id, user_id, work_package_id, hours, activity_id, spent_on, tyear, tmonth, tweek, created_on, updated_on, costs, toggl_id) VALUES ('{$project_id}', '{$worker[$x]}', '{$work_package_id}', '{$hours}', '1', '{$date_toggl_entrie}', '{$ano}', '{$mes}', '{$week}', '{$date_now}', '{$date_now}', '{$costs}', '{$row->id}')";
                            if (pg_query($db_connect, $sql)) {

                            } else {
                                echo "Error Introduciendo Tiempo: " . $sql . "<br>" . pg_result_error($db_connect);
                            }
                        }
                    }
                    elseif (!empty($project_id) && empty($work_package_id)){
                        $sql_proyecto_modulo = pg_query($db_connect, "SELECT id FROM work_packages WHERE project_id = '{$project_id}' AND subject LIKE '%Others%'");
                        $row_id_modul = pg_fetch_assoc($sql_proyecto_modulo);
                        $work_package_id = $row_id_modul['id'];

                        if(!empty($work_package_id)){
                            $arr = explode("T", $row->start);
                            $date_toggl_entrie = $arr[0];
                            $arr = explode("-", $date_toggl_entrie);
                            $ano = $arr[0];
                            $mes_con_cero = $arr[1];
                            $mes = (int)$mes_con_cero;
                            $week = date("W", strtotime($date_toggl_entrie));
                            $date_now = date("Y-m-d H:i:s"); 

                            $seconds = $row->duration;
                            $minutes = $row->duration/60;
                            $hours = round($minutes/60, 2);
                            $costs = '0.0000';
                    
                            if ($hours > 0){
                                $sql = "INSERT INTO time_entries (project_id, user_id, work_package_id, hours, comments, activity_id, spent_on, tyear, tmonth, tweek, created_on, updated_on, costs, toggl_id) VALUES ('{$project_id}', '{$worker[$x]}', '{$work_package_id}', '{$hours}', '{$row->description}', '1', '{$date_toggl_entrie}', '{$ano}', '{$mes}', '{$week}', '{$date_now}', '{$date_now}', '{$costs}', '{$row->id}')";
                                if (pg_query($db_connect, $sql)) {
                    
                                } else {
                                    echo "Error Insertando Primera Vez Id Proyecto: " . $sql . "<br>" . pg_result_error($db_connect);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
pg_close($db_connect);

?>