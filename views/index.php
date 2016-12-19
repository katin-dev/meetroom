<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="windows-1251">
    <title>Свободные часы для занятий</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.min.css"/>
    <link rel="stylesheet" href="/style.css"/>

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/locales/bootstrap-datepicker.ru.min.js"></script>
    <script>
        $(function () {
            $('.dtpckr').datepicker({
                format: "yyyy-mm-dd",
                todayBtn: "linked",
                language: "ru",
                autoclose: true
            });

            $('#SetDate .dtpckr').on('changeDate', function (e) {
               $(e.target).parents('form').eq(0).submit();
            });

        });
    </script>
</head>
<body>
<div class="container">
    <h1>
        Переговорки
        <form action="" method="get" id="SetDate">
            <input type="date" name="date" value="<?=$date?>" class="dtpckr" />
            <input type="submit" value="OK" class="btn btn-success"/>
        </form>
    </h1>
    <?=$body?>
    <p></p>
    <div class="panel panel-default">
        <div class="panel-heading">
            <strong data-toggle="ReserveForm">Забронировать переговорку</strong>
        </div>
        <div class="panel-body" id="ReserveForm">
            <form action="" method="post">
                <div class="form-group">
                    <label>Какую?</label>
                    <select name="room_id" class="form-control">
                      <?php foreach ($rooms as $room): ?>
                          <option value="<?=$room['id']?>"><?=$this->e($room['name'])?></option>
                      <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-inline">
                    <div class="form-group">
                        <label>Когда?</label>
                        <input type="date" value="<?=$date?>" class="dtpckr" name="date" style="width:85px;"/>
                        С
                        <select name="from_hour">
                          <?php
                            echo implode("\n", array_map(function ($h) {
                              return sprintf('<option value="%s">%02d</option>', $h, $h);
                            }, range(7, 23)));
                          ?>
                        </select>
                        <select name="from_minute">
                          <?php for($m = 0; $m < 60; $m += 5): ?>
                            <option value="<?=$m?>"><?=sprintf('%02d', $m)?></option>
                          <?php endfor ?>
                        </select>
                    </div>
                    <div class="form-group">
                        до
                        <select name="to_hour">
                        <?php
                        echo implode("\n", array_map(function ($h) {
                          return sprintf('<option value="%s">%02d</option>', $h, $h);
                        }, range(7, 23)));
                        ?>
                        </select>
                        <select name="to_minute">
                          <?php for($m = 0; $m < 60; $m += 5): ?>
                              <option value="<?=$m?>"><?=sprintf('%02d', $m)?></option>
                          <?php endfor ?>
                        </select>
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
    </div>
</div>
</body>
</html>