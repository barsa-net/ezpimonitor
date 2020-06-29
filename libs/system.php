<?php
require '../autoload.php';
date_default_timezone_set(@date_default_timezone_get());

// Hostname
$hostname = Misc::getHostname();

// OS
if (!file_exists('/usr/bin/lsb_release') || !($os = shell_exec('/usr/bin/lsb_release -ds | cut -d= -f2 | tr -d \'"\'')))
{
    if (!file_exists('/etc/system-release') || !($os = shell_exec('cat /etc/system-release | cut -d= -f2 | tr -d \'"\'')))
    {
        if (!file_exists('/etc/os-release') || !($os = shell_exec('cat /etc/os-release | grep PRETTY_NAME | tail -n 1 | cut -d= -f2 | tr -d \'"\'')))
        {
            if (!($os = shell_exec('find /etc/*-release -type f -exec cat {} \; | grep PRETTY_NAME | tail -n 1 | cut -d= -f2 | tr -d \'"\'')))
            {
                $os = 'N.A';
            }
        }
    }
}
$os = trim($os, '"');
$os = str_replace("\n", '', $os);

// Hypriot
if (!file_exists('/dockerhost/usr/lib/os-release') || !($hypriot = shell_exec('cat /dockerhost/usr/lib/os-release | grep HYPRIOT_IMAGE_VERSION | tail -n 1 | cut -d= -f2 | tr -d \'"\'')))
{
    if (!file_exists('/usr/lib/os-release') || !($hypriot = shell_exec('cat /usr/lib/os-release | grep HYPRIOT_IMAGE_VERSION | tail -n 1 | cut -d= -f2 | tr -d \'"\'')))
    {
        $hypriot = 'N.A';
    }
}

$hypriot = trim($hypriot);

// Kernel
if (!($kernel = shell_exec('/bin/uname -r')))
{
    $kernel = 'N.A';
}

$kernel = trim($kernel);

// Uptime
if (!($totalSeconds = shell_exec('/usr/bin/cut -d. -f1 /proc/uptime')))
{
    $uptime = 'N.A';
}
else
{
    $uptime = Misc::getHumanTime((int)$totalSeconds);
}

// Last boot
if (!($upt_tmp = shell_exec('cat /proc/uptime')))
{
    $last_boot = 'N.A';
}
else
{
    $upt = explode(' ', $upt_tmp);
    $last_boot = date('Y-m-d H:i:s', time() - intval($upt[0]));
}

// Current users
if(file_exists(($utmp = "/dockerhost/var/run/utmp")))
{

    $utmp_size = 384;

    if(!($uf = fopen($utmp, 'rb'))){
        return;
    }

    $current_users = 0;
    for($i=0;$chunk = fgets($uf, 3);){
        if(($type = unpack( "s", $chunk)[1]) == 7)
            $current_users++;
        fseek($uf,++$i*$utmp_size);
    }

    fclose($uf);
}
else
{
    if (!($current_users = shell_exec('who -u | awk \'{ print $1 }\' | wc -l')))
    {
        $current_users = 'N.A';
    }
}

// Server datetime
if (!($server_date = shell_exec('/bin/date')))
{
    $server_date = date('Y-m-d H:i:s');
}

$server_date = trim($server_date);


$datas = array(
    'hostname'      => $hostname,
    'os'            => $os,
    'hypriot'       => $hypriot,
    'kernel'        => $kernel,
    'uptime'        => $uptime,
    'last_boot'     => $last_boot,
    'current_users' => $current_users,
    'server_date'   => $server_date,
);

echo json_encode($datas);
