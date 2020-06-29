<?php
require '../autoload.php';
$Config = new Config();


$datas = array();

if ($Config->get('last_login:enable'))
{
    if(!file_exists('/dockerhost/var/log/lastlog') || !file_exists('/dockerhost/etc/passwd'))
    {
        if (!(exec('/usr/bin/lastlog --time 365 | awk \'{ printf $1";"; for (i=4; i<NF; i++) printf $i" "; print $NF; }\'', $users)))
        {
            $datas[] = array(
                'user' => 'N.A',
                'date' => 'N.A',
            );
        }
        else
        {
            $max = $Config->get('last_login:max');

            for ($i = 1; $i < count($users) && $i <= $max; $i++)
            {
                list($user, $date) = explode(';', $users[$i]);

                $datas[] = array(
                    'user' => $user,
                    'date' => $date,
                );
            }
        }
    }
    else
    {

        $llog_size = 292;
        $llog_namesize = 32;
        $llog_hostsize = 256;

        // ############################################
        if (!(exec('cat /dockerhost/etc/passwd | awk -F: \'{print $3,$1}\' | sort -nk1', $users)))
        {
            $datas[] = array(
                'user' => 'N.A',
                'date' => 'N.A',
            );
        }
        else
        {
            $lf = fopen("/dockerhost/var/log/lastlog", 'rb');

            foreach($users as $user){

                $userinfo = explode(" ", $user);
                if(($uid = $userinfo[0]) == 65534)
                    continue;

                fseek($lf, $uid*$llog_size);

                if(!($utime = unpack( "I", fgets($lf, 5) ))[1])
                    continue;

                $date = DateTime::createFromFormat("U", $utime[1]);
                if(is_null($_ENV["TZ"]))
                {
                    $date->setTimezone(new DateTimeZone(exec('cat /etc/timezone')));
                }
                else
                {
                    $date->setTimezone(new DateTimeZone($_ENV["TZ"]));
                }
                $datas[] = array(
                    'user' => $userinfo[1],
                    'date' => $date->format("D M j H:i:s O Y"),
                );
            }

            fclose($lf);
        }
    }
}

echo json_encode($datas);
