<?php

foreach($pilots as $pilot)
{
?>
<p><a href="<?=SITE_URL?>/profile/<?=$pilot->pilotid?>"><?=PilotData::GetPilotCode($pilot->code, $pilot->pilotid). ' ' .$pilot->firstname . ' ' . $pilot->lastname?></a></p>
<?php
}
?>