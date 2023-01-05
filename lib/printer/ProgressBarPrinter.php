<?php

class ProgressBarPrinter
{
    public static function printBar(ComplianceProgramStatus $status, $limit)
    {
        $width = 40; //min(100, $status->getPoints() / $limit * 100);
        $class = $status->isCompliant() ? 'bar-success' : 'bar-warning';
        ?>
        <div class="progress">
            <div class="bar <?php echo $class ?>" style="width: <?php echo $width ?>%;">
                <?php if ($width >= 40) : ?>
                    <?php echo $status->getPoints() ?> points earned  (<?php echo $limit ?> needed)
                <?php endif ?>
            </div>
            <?php if ($width < 40) : ?>
                <div style="color:#333; text-align:right;font-size:smaller;">
                    <?php echo $status->getPoints() ?> points earned (<?php echo $limit ?> needed)
                </div>
            <?php endif ?>
        </div>
        <?php
    }
}