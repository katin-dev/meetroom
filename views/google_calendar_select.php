<strong>Укажите, какие календари соответсвуют переговоркам:</strong>
<form action="" method="POST">
    <?php foreach ($calendars as $calendar): ?>
    <div class="checkbox">
        <label><input type="checkbox" name="calendar[]" value="<?=$calendar->id?>"/> <?=$calendar->summary?></label>
    </div>
    <?php endforeach; ?>
    <input type="submit" name="submit" value="Сохранить" />
</form>