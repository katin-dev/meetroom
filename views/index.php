<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="windows-1251">
    <title>Переговорки</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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

            $('[name="from_hour"]').change(function () {
                $('[name="to_hour"]').val(parseInt($(this).val()) + 1);
            });

            $('.calender-slot').popover();
            $('.calender-hour').click(function () {
                var slot = $(this),
                    room_id = slot.siblings('.calendar-dayname').data('id'),
                    hour = parseInt(slot.data('hour')),
                    modal = $('#ReserveModal');

                modal.find('[name="room_id"]').val(room_id);
                modal.find('[name="from_hour"]').val(hour);
                modal.find('[name="to_hour"]').val(hour + 1);
                modal.modal('show');
            });
        });
    </script>
</head>
<body>
<div class="container-fluid">
    <h1 class="container">
        <img src="/profi-logo.svg" />
        Переговорки
        <form action="" method="get" id="SetDate">
            <input type="text" name="date" value="<?=$date?>" class="dtpckr" />
        </form>
    </h1>
    <?=$body?>
    <div class="container">
    <div class="modal fade" tabindex="-1" id="ReserveModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="post">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Забронировать переговорку</h4>
                </div>
                <div class="modal-body">
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
                                <input type="text" value="<?=$date?>" class="dtpckr" name="date" style="width:85px;"/>
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
                            <div class="form-group">
                                Повторять:
                                <select name="repeat">
                                    <option value="">-</option>
                                    <option value="day">Каждый день</option>
                                    <option value="week">Каждую неделю</option>
                                    <option value="month">Каждый месяц</option>
                                </select>
                            </div>
                        </div>
                        <p></p>
                        <div class="form-group">
                            <label>Комментарий</label>
                            <textarea name="comment" class="form-control"></textarea>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Забронировать</button>
                </div>
                </form>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
    </div>
</div>
</body>
</html>