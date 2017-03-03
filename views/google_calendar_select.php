<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Выбор календарей</title>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    </head>
    <body>
        <div class="container">
            <h3>Выберете календари, которые соответсвуют вашим переговоркам:</h3>
            <form action="" method="POST">
              <?php foreach ($calendars as $calendar): ?>
                  <div class="checkbox">
                      <label><input type="checkbox" name="calendar[]" value="<?=$calendar->id?>"/> <?=$calendar->summary?></label>
                  </div>
              <?php endforeach; ?>
                <input type="submit" name="submit" value="Сохранить" class="btn btn-success" />
            </form>
        </div>
    </body>
</html>