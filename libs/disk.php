<?php
require '../autoload.php';
$Config = new Config();

$datas = array();

# if there are more than 7 awk's colums it means the mount point name contains spaces
# so consider the first colums as a unique colum and the last 6 as real colums
if (!(exec('/bin/df -T -P | tail -n +2 | awk \'{ if (NF > 7) { for (i=1; i<NF-6; i++) { printf("%s ", $i); } for (i=NF-6; i<NF; i++) { printf("%s,", $i); } print $NF; } else { print $1","$2","$3","$4","$5","$6","$7; } }\'', $df)))
{
    $datas[] = array(
        'total'         => 'N.A',
        'used'          => 'N.A',
        'free'          => 'N.A',
        'percent_used'  => 0,
        'mount'         => 'N.A',
        'filesystem'    => 'N.A',
    );
}
else
{
    $parsedMounted = array(
            'points'        => array(),
            'total'         => array(),
            'used'          => array(),
            'free'          => array(),
            'percent'  => array(),
        );
    $key = 0;

    foreach ($df as $mounted)
    {
        list($filesystem, $type, $total, $used, $free, $percent, $mount) = explode(',', $mounted);

        if ($percent > 100)
            $percent = 100;

        if (strpos($type, 'tmpfs') !== false && $Config->get('disk:show_tmpfs') === false)
            continue;

        if (strpos($filesystem, '/dev/loop') !== false && $Config->get('disk:show_loop') === false)
            continue;

        foreach ($Config->get('disk:ignore_mounts') as $to_ignore)
        {
            if ($mount === $to_ignore)
                continue 2;
        }

        $Mpercent = trim($percent, '%');

        # Check for duplicates
        if(!in_array($mount, $parsedMounted['points']) && !in_array($total, $parsedMounted['total']) && !in_array($used, $parsedMounted['used']) && !in_array($free, $parsedMounted['free']) && !in_array($Mpercent, $parsedMounted['percent']))
        {
            $parsedMounted['points'][] = trim($mount);
            $parsedMounted['total'][] = $total;
            $parsedMounted['used'][] = $used;
            $parsedMounted['free'][] = $free;
            $parsedMounted['percent'][] = $Mpercent;

            $datas[$key] = array(
                'total'         => Misc::getSize($total * 1024),
                'used'          => Misc::getSize($used * 1024),
                'free'          => Misc::getSize($free * 1024),
                'percent_used'  => $Mpercent,
                'mount'         => $mount,
            );

            if ($Config->get('disk:show_filesystem'))
                $datas[$key]['filesystem'] = $filesystem;
        }

        $key++;
    }

}

if ($Config->get('disk:show_filesystem'))
{
    usort($datas, function($a, $b) {
        return $a['filesystem'] <=> $b['filesystem'];
    });
}

echo json_encode($datas);
