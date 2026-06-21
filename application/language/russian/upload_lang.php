<?php
// Запрещаем прямой доступ к файлу минуя фреймворк
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Языковой файл библиотеки Upload (русский).
 * Сообщения об ошибках при загрузке файлов.
 */

$lang['upload_userfile_not_set']          = 'Не найдена POST-переменная с файлом.';
$lang['upload_file_exceeds_limit']        = 'Размер файла превышает максимально допустимый в настройках PHP.';
$lang['upload_file_exceeds_form_limit']   = 'Размер файла превышает максимально допустимый для формы.';
$lang['upload_file_partial']              = 'Файл был загружен лишь частично.';
$lang['upload_no_temp_directory']         = 'Временная папка не найдена.';
$lang['upload_unable_to_write_file']      = 'Не удалось записать файл на диск.';
$lang['upload_stopped_by_extension']      = 'Загрузка файла остановлена расширением.';
$lang['upload_no_file_selected']          = 'Файл для загрузки не выбран.';
$lang['upload_invalid_filetype']          = 'Данный тип файла не разрешён для загрузки.';
$lang['upload_invalid_filesize']          = 'Файл превышает допустимый размер.';
$lang['upload_invalid_dimensions']        = 'Изображение не соответствует допустимым размерам.';
$lang['upload_destination_error']         = 'Ошибка при перемещении файла в конечную директорию.';
$lang['upload_no_filepath']               = 'Путь для загрузки некорректен.';
$lang['upload_no_file_types']             = 'Не указаны допустимые типы файлов.';
$lang['upload_bad_filename']              = 'Файл с таким именем уже существует на сервере.';
$lang['upload_not_writable']              = 'Директория загрузки не доступна для записи.';
