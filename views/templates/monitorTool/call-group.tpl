<?php
/**
 * @var $adapter \tao\taoDevTools\models\Monitor\OutputAdapter\Tao
 * @var $calls \tao\taoDevTools\models\Monitor\Chunk\CallChunk[]
 */
?>
<ul>
    <?php
    $mergedTraceInfo = get_data('mergedTraceInfo');
    ?>

    <li>
        <h1><?php echo $mergedTraceInfo['count']; ?> Calls </h1>
    </li>
    <li>
        <ul>
            Parameters:
            <li><pre><?= print_r($mergedTraceInfo['params'], true) ?></pre></li>
        </ul>
    </li>
    <li>
        <img src="<?= $mergedTraceInfo['umlSrc'] ?>">
    </li>
    <li>
        <span>Common starting trace :</span>
        <ul>
            <?
        $count = 1;
        foreach($mergedTraceInfo['commonStartingParts'] as $trace ) :?>
            <li><?= str_repeat('&nbsp;', $count++) ?> => <?= $trace['function']?> [<?= $trace['file'] ?> (<?= $trace['line'] ?>)]</li>
            <?php endforeach; ?>
        </ul>
    </li>


    <?php foreach($mergedTraceInfo['mergedTraces'] as $mergedTrace) :?>
    <li>
        <span>Nbr call for this trace : <?= $mergedTrace['count'] ?></span>
        <ul>
            <?php
            $count = 1;

            if(!count($mergedTrace['diffTrace']))
            {
                //Here we found a trace that is completely represented by the starting and ending parts so
                //there is no difference to show
                echo 'This trace is completely contained in starting and ending parts.';
            }
            foreach($mergedTrace['diffTrace'] as $trace ) : $traceId = 'trace-id-' . uniqid(); ?>
            <li>
                <a data-toggle="collapse"  href="#">
                    <?= $trace['function']?> [<?= $trace['file'] ?> (<?= $trace['line'] ?>)]
                </a>
                    <div style="display:none;">
                        <style>
                            .class<?php echo $traceId ?> li:nth-child(<?php echo $trace['line'] - $trace['methodSrc']['startOffset']?> ) { background: rgb(255,200,200) }
                        </style>
                        <pre class="class<?= $traceId ?> prettyprint linenums:<?= $trace['methodSrc']['startOffset'] +1 ?> linenums"><?= $trace['methodSrc']['src'] ?></pre>
                    </div>
            </li>

            <?php endforeach; ?>
        </ul>
        <?php endforeach; ?>
    </li>
    <li>

        <span>Common ending trace :</span>
        <ul>
            <?php
        $count = 1;
        foreach($mergedTraceInfo['commonEndingParts'] as $trace ) :?>
            <li><?= str_repeat('&nbsp;', $count++) ?> => <?= $trace['function']?> [<?= $trace['file'] ?> (<?= $trace['line'] ?>)]</li>
            <?php endforeach; ?>
        </ul>
    </li>
</ul>