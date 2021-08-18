<?php

$mod = FreePBX::Synologyactivebackupforbusiness();

//echo FreePBX::Synologyactivebackupforbusiness()->showPage();
echo $mod->showPage();



echo "<br>";
echo "Version:". $mod->getAgentVersion();
echo "<br>";

echo "<br>";
echo "Status:<br>";
print_r($mod->getAgentStatus());
echo "<br>";