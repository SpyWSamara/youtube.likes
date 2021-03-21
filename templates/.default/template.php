<?php

/** @var array $arResult */

?>
<div class="youtube-search-wrapper">
    <form action="#" class="youtube-search">
        <input type="hidden" name="signedParameters"
               value="<?= $arResult['SIGNED_PARAMETERS'] ?>">
        <input type="text" name="q" value="">
    </form>
    <p class="status"></p>
    <ul class="result"></ul>
</div>
