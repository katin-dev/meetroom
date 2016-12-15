<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="windows-1251">
    <title>Свободные часы для занятий</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="/style.css"/>
</head>
<body>
<div class="container">
    <h1>Занятость переговорок</h1>
    <?=$body?>
    <form action="" method="post">
        <h2>Забронировать переговорку</h2>
        <div class="form-group">
            <label>Выберите переговорку:</label>
            <select name="id" class="form-control">
              <?php foreach ($rooms as $room): ?>
                  <option value="<?=$room['id']?>"><?=$this->e($room['name'])?></option>
              <?php endforeach; ?>
            </select>
        </div>
        <div class="form-inline">
            <div class="form-group">
                С <input type="date" class="form-control" name="from" />
            </div>
            <div class="form-group">
                до <input type="date" class="form-control" name="to" />
            </div>
        </div>
        <p></p>
        <div class="form-group">
            <label>Комментарий</label>
            <textarea name="comment" class="form-control"></textarea>
        </div>

        <input type="submit" name="add" value="Забронировать" class="btn btn-success" />
    </form>
</div>
</body>
</html>