<?php foreach ($events->getItems() as $event): ?>
    <article>
        <h2><?=$event->getSummary()?></h2>
        <p><?=$event->start->dateTime?> &mdash; <?=$event->end->dateTime?></p>
    </article>
<?php endforeach; ?>