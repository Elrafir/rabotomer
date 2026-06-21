<?php
// Запрещаем прямой доступ к файлу минуя фреймворк
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Модель для работы с заказчиками, техническими заданиями (ТЗ) и файлами ТЗ.
 *
 * Содержит:
 * - CRUD-операции для заказчиков (customers)
 * - CRUD-операции для ТЗ (customer_specs)
 * - CRUD-операции для файлов ТЗ (customer_spec_files)
 * - Привязку/отвязку задач к ТЗ (tasks.spec_id)
 * - Проверку прав владения (verify_*_ownership)
 * - Автомиграции схемы БД (_run_migrations)
 *
 * Текущая версия схемы: 7
 */
class Customer_model extends CI_Model {

    /**
     * Актуальная версия миграций БД модуля «Заказчики».
     * При добавлении новой миграции — увеличить на 1.
     */
    const DB_VERSION = '7';

    /**
     * Конструктор модели.
     * Запускает автомиграции при первом обращении к модели.
     */
    public function __construct()
    {
        // Вызов конструктора родительского класса CI_Model
        parent::__construct();

        // Запуск автомиграций — проверит версию и выполнит недостающие
        $this->_run_migrations();
    }

    // =========================================================================
    // АВТОМИГРАЦИИ СХЕМЫ БАЗЫ ДАННЫХ
    // =========================================================================

    /**
     * Выполняет автоматические миграции схемы БД для модуля «Заказчики».
     *
     * Логика:
     * 1. Читаем текущую версию из settings ('customers_db_version')
     * 2. Если версия < актуальной — последовательно выполняем миграции
     * 3. Обновляем версию в settings после завершения
     *
     * Все миграции идемпотентны — проверяют существование таблиц/колонок перед действием.
     *
     * @return void
     */
    private function _run_migrations()
    {
        // Загружаем Settings_model для хранения версии миграций
        $this->load->model('Settings_model');

        // Получаем текущую версию миграций из настроек (по умолчанию '0' — ни одна не выполнена)
        $current_version = $this->Settings_model->get_setting('customers_db_version', '0');

        // Если версия БД уже актуальна — ничего не делаем
        if ($current_version >= self::DB_VERSION) {
            return;
        }

        // --- Миграция 1: Замена hourly_rate на notes в таблице customers ---
        if ($current_version < '1') {
            // Добавляем текстовое поле notes для заметок о заказчике
            if (!$this->db->field_exists('notes', 'customers')) {
                $this->db->query("ALTER TABLE customers ADD COLUMN notes TEXT NULL AFTER name");
                // Удаляем устаревшую колонку hourly_rate, если она ещё существует
                if ($this->db->field_exists('hourly_rate', 'customers')) {
                    $this->db->query("ALTER TABLE customers DROP COLUMN hourly_rate");
                }
            }
        }

        // --- Миграция 2: Создание таблицы customer_specs для хранения ТЗ ---
        if ($current_version < '2') {
            if (!$this->db->table_exists('customer_specs')) {
                $this->db->query("
                    CREATE TABLE customer_specs (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        customer_id INT UNSIGNED NOT NULL,
                        title VARCHAR(255) NOT NULL,
                        content TEXT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CONSTRAINT fk_spec_customer_id FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
        }

        // --- Миграция 3: Добавление files_dir и создание таблицы customer_spec_files ---
        if ($current_version < '3') {
            // Колонка files_dir — путь к внешней директории с рабочими материалами
            if (!$this->db->field_exists('files_dir', 'customer_specs')) {
                $this->db->query("ALTER TABLE customer_specs ADD COLUMN files_dir VARCHAR(255) NULL AFTER payment_type");
            }

            // Таблица для хранения прикреплённых файлов ТЗ
            if (!$this->db->table_exists('customer_spec_files')) {
                $this->db->query("
                    CREATE TABLE customer_spec_files (
                        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        spec_id INT UNSIGNED NOT NULL,
                        filename VARCHAR(255) NOT NULL,
                        orig_name VARCHAR(255) NOT NULL,
                        file_size INT UNSIGNED NOT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CONSTRAINT fk_file_spec_id FOREIGN KEY (spec_id) REFERENCES customer_specs (id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
        }

        // --- Миграция 4: Финансовые дефолты в таблице customers ---
        if ($current_version < '4') {
            // Ценник по умолчанию для всех ТЗ заказчика
            if (!$this->db->field_exists('default_price', 'customers')) {
                $this->db->query("ALTER TABLE customers ADD COLUMN default_price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER notes");
            }
            // Предоплата по умолчанию
            if (!$this->db->field_exists('default_prepayment', 'customers')) {
                $this->db->query("ALTER TABLE customers ADD COLUMN default_prepayment DECIMAL(10,2) NULL DEFAULT 0.00 AFTER default_price");
            }
            // Тип оплаты по умолчанию (hourly/fixed)
            if (!$this->db->field_exists('default_payment_type', 'customers')) {
                $this->db->query("ALTER TABLE customers ADD COLUMN default_payment_type VARCHAR(50) NULL DEFAULT 'hourly' AFTER default_prepayment");
            }
        }

        // --- Миграция 5: Финансовые поля в ТЗ (customer_specs) ---
        if ($current_version < '5') {
            // Цена конкретного ТЗ
            if (!$this->db->field_exists('price', 'customer_specs')) {
                $this->db->query("ALTER TABLE customer_specs ADD COLUMN price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER title");
            }
            // Предоплата для ТЗ
            if (!$this->db->field_exists('prepayment', 'customer_specs')) {
                $this->db->query("ALTER TABLE customer_specs ADD COLUMN prepayment DECIMAL(10,2) NULL DEFAULT 0.00 AFTER price");
            }
            // Тип оплаты для ТЗ
            if (!$this->db->field_exists('payment_type', 'customer_specs')) {
                $this->db->query("ALTER TABLE customer_specs ADD COLUMN payment_type VARCHAR(50) NULL DEFAULT 'hourly' AFTER prepayment");
            }
        }

        // --- Миграция 6: Флаг is_link для внешних ссылок в файлах ТЗ ---
        if ($current_version < '6') {
            if (!$this->db->field_exists('is_link', 'customer_spec_files')) {
                $this->db->query("ALTER TABLE customer_spec_files ADD COLUMN is_link TINYINT UNSIGNED DEFAULT 0 AFTER file_size");
            }
        }

        // --- Миграция 7: Поле привязки ТЗ в таблице tasks ---
        if ($current_version < '7') {
            if (!$this->db->field_exists('spec_id', 'tasks')) {
                $this->db->query("ALTER TABLE tasks ADD COLUMN spec_id INT UNSIGNED NULL DEFAULT NULL AFTER customer_id");
            }
        }

        // Обновляем версию миграций в настройках, чтобы не выполнять повторно
        $this->Settings_model->set_setting('customers_db_version', self::DB_VERSION);
    }

    // =========================================================================
    // CRUD-ОПЕРАЦИИ ДЛЯ ЗАКАЗЧИКОВ (customers)
    // =========================================================================

    /**
     * Получить список всех заказчиков пользователя
     *
     * @param int      $user_id ID авторизованного пользователя
     * @param int|null $limit   Лимит записей (null — без ограничений)
     * @param int|null $offset  Смещение для пагинации
     * @return array Список заказчиков, отсортированных по имени ASC
     */
    public function get_all($user_id, $limit = NULL, $offset = NULL)
    {
        // Фильтруем по пользователю — каждый видит только своих заказчиков
        $this->db->where('user_id', $user_id);

        // Сортируем по имени в алфавитном порядке
        $this->db->order_by('name', 'ASC');

        // Применяем пагинацию, если задан лимит
        if ($limit !== NULL) {
            $this->db->limit($limit, $offset);
        }

        // Выполняем запрос и возвращаем результат как массив ассоциативных массивов
        return $this->db->get('customers')->result_array();
    }

    /**
     * Получить заказчика по ID с проверкой принадлежности пользователю
     *
     * @param int $customer_id ID заказчика
     * @param int $user_id     ID пользователя
     * @return array|null Данные заказчика или null, если не найден
     */
    public function get_by_id($customer_id, $user_id)
    {
        // Фильтр по ID заказчика
        $this->db->where('id', $customer_id);

        // Фильтр по владельцу — защита от горизонтальной эскалации
        $this->db->where('user_id', $user_id);

        // Возвращаем одну строку или null
        return $this->db->get('customers')->row_array();
    }

    /**
     * Добавить нового заказчика с дефолтными финансовыми параметрами
     *
     * @param int    $user_id              ID пользователя-владельца
     * @param string $name                 Название заказчика
     * @param string $notes                Заметки
     * @param float  $default_price        Ценник по умолчанию
     * @param float  $default_prepayment   Предоплата по умолчанию
     * @param string $default_payment_type Тип оплаты по умолчанию (fixed/hourly)
     * @return int ID созданной записи
     */
    public function add($user_id, $name, $notes, $default_price = 0.00, $default_prepayment = 0.00, $default_payment_type = 'hourly')
    {
        // Формируем массив данных для вставки
        $data = [
            'user_id'              => $user_id,
            'name'                 => $name,
            'notes'                => $notes,
            'default_price'        => (float)$default_price,
            'default_prepayment'   => (float)$default_prepayment,
            'default_payment_type' => $default_payment_type,
        ];

        // Вставляем запись в таблицу заказчиков
        $this->db->insert('customers', $data);

        // Возвращаем auto_increment ID вновь созданной записи
        return $this->db->insert_id();
    }

    /**
     * Обновить данные заказчика с дефолтными финансовыми параметрами
     *
     * @param int    $customer_id          ID заказчика
     * @param int    $user_id              ID пользователя (для проверки прав)
     * @param string $name                 Название заказчика
     * @param string $notes                Заметки
     * @param float  $default_price        Ценник по умолчанию
     * @param float  $default_prepayment   Предоплата по умолчанию
     * @param string $default_payment_type Тип оплаты по умолчанию
     * @return bool Результат выполнения UPDATE
     */
    public function update($customer_id, $user_id, $name, $notes, $default_price = 0.00, $default_prepayment = 0.00, $default_payment_type = 'hourly')
    {
        // Формируем массив обновляемых полей
        $data = [
            'name'                 => $name,
            'notes'                => $notes,
            'default_price'        => (float)$default_price,
            'default_prepayment'   => (float)$default_prepayment,
            'default_payment_type' => $default_payment_type,
        ];

        // Фильтр по ID заказчика
        $this->db->where('id', $customer_id);

        // Фильтр по владельцу — нельзя редактировать чужого заказчика
        $this->db->where('user_id', $user_id);

        // Выполняем обновление записи
        return $this->db->update('customers', $data);
    }

    /**
     * Удалить заказчика (каскадно удалятся ТЗ и файлы по FK)
     *
     * @param int $customer_id ID заказчика
     * @param int $user_id     ID пользователя (для проверки прав)
     * @return bool Результат выполнения DELETE
     */
    public function delete($customer_id, $user_id)
    {
        // Фильтр по ID заказчика
        $this->db->where('id', $customer_id);

        // Фильтр по владельцу — защита от удаления чужих данных
        $this->db->where('user_id', $user_id);

        // Выполняем удаление (FK CASCADE удалит связанные ТЗ и файлы)
        return $this->db->delete('customers');
    }

    // =========================================================================
    // МЕТОДЫ РАБОТЫ С ТЕХНИЧЕСКИМИ ЗАДАНИЯМИ (ТЗ)
    // =========================================================================

    /**
     * Получить список ТЗ для заказчика (без файлов и linked_tasks)
     *
     * @param int $customer_id ID заказчика
     * @return array Список ТЗ, отсортированных по дате создания DESC
     */
    public function get_specs($customer_id)
    {
        // Фильтр по заказчику
        $this->db->where('customer_id', $customer_id);

        // Новые ТЗ — сверху
        $this->db->order_by('created_at', 'DESC');

        // Возвращаем все ТЗ заказчика
        return $this->db->get('customer_specs')->result_array();
    }

    /**
     * Получить конкретное ТЗ по его ID
     *
     * @param int $spec_id ID ТЗ
     * @return array|null Данные ТЗ или null, если не найдено
     */
    public function get_spec($spec_id)
    {
        // Фильтр по ID ТЗ
        $this->db->where('id', $spec_id);

        // Возвращаем одну запись
        return $this->db->get('customer_specs')->row_array();
    }

    /**
     * Получить ВСЕ ТЗ заказчика вместе с файлами и linked_task_ids.
     *
     * Устраняет проблему N+1 запросов: вместо цикла по каждому ТЗ
     * (1 запрос specs + N запросов files + N запросов tasks)
     * делаем всего 3 запроса:
     * 1. Все specs заказчика
     * 2. Все files для всех spec_ids (одним WHERE IN)
     * 3. Все linked tasks для всех spec_ids (одним WHERE IN)
     *
     * @param int $customer_id ID заказчика
     * @param int $user_id     ID пользователя (для фильтрации задач)
     * @return array Массив ТЗ, каждый содержит 'files', 'linked_task_ids', 'external_files'
     */
    public function get_specs_with_files($customer_id, $user_id)
    {
        // Шаг 1: Получаем все ТЗ заказчика базовым методом
        $specs = $this->get_specs($customer_id);

        // Если у заказчика нет ТЗ — возвращаем пустой массив
        if (empty($specs)) {
            return [];
        }

        // Собираем массив ID всех ТЗ для batch-запросов
        $spec_ids = array_column($specs, 'id');

        // Шаг 2: Одним запросом получаем ВСЕ файлы для ВСЕХ ТЗ заказчика
        $this->db->where_in('spec_id', $spec_ids);
        $all_files = $this->db->get('customer_spec_files')->result_array();

        // Группируем файлы по spec_id для быстрого доступа
        $files_by_spec = [];
        foreach ($all_files as $file) {
            // Раскладываем файлы по «полочкам» spec_id
            $files_by_spec[$file['spec_id']][] = $file;
        }

        // Шаг 3: Одним запросом получаем ВСЕ привязанные задачи для ВСЕХ ТЗ
        $this->db->select('id, spec_id');
        $this->db->where_in('spec_id', $spec_ids);
        $this->db->where('customer_id', $customer_id);
        $this->db->where('user_id', $user_id);
        $this->db->where('deleted_at IS NULL', NULL, FALSE);
        $all_linked_tasks = $this->db->get('tasks')->result_array();

        // Группируем task_ids по spec_id
        $task_ids_by_spec = [];
        foreach ($all_linked_tasks as $task) {
            // Собираем только ID задач для каждого ТЗ
            $task_ids_by_spec[$task['spec_id']][] = $task['id'];
        }

        // Шаг 4: Загружаем хелпер для сканирования внешних файлов
        $this->load->helper('file_manager');

        // Шаг 5: Распределяем файлы и task_ids по каждому ТЗ
        foreach ($specs as &$spec) {
            // Подставляем файлы для текущего ТЗ (или пустой массив)
            $spec['files'] = isset($files_by_spec[$spec['id']])
                ? $files_by_spec[$spec['id']]
                : [];

            // Подставляем ID привязанных задач (или пустой массив)
            $spec['linked_task_ids'] = isset($task_ids_by_spec[$spec['id']])
                ? $task_ids_by_spec[$spec['id']]
                : [];

            // Сканируем внешнюю директорию с рабочими материалами, если задана
            $spec['external_files'] = [];
            if (!empty($spec['files_dir'])) {
                $spec['external_files'] = scan_external_files($spec['files_dir']);
            }
        }
        // Снимаем ссылку после цикла по ссылке
        unset($spec);

        // Возвращаем обогащённый массив ТЗ
        return $specs;
    }

    /**
     * Добавить новое ТЗ для заказчика
     *
     * @param int         $customer_id  ID заказчика
     * @param string      $title        Название ТЗ
     * @param string      $content      HTML-текст ТЗ из редактора
     * @param float       $price        Ценник ТЗ
     * @param float       $prepayment   Предоплата ТЗ
     * @param string      $payment_type Тип оплаты ТЗ (fixed/hourly)
     * @param string|null $files_dir    Путь к внешней директории с файлами
     * @return int ID созданного ТЗ
     */
    public function add_spec($customer_id, $title, $content, $price = 0.00, $prepayment = 0.00, $payment_type = 'hourly', $files_dir = NULL)
    {
        // Формируем массив данных для вставки
        $data = [
            'customer_id'  => $customer_id,
            'title'        => $title,
            'content'      => $content,
            'price'        => (float)$price,
            'prepayment'   => (float)$prepayment,
            'payment_type' => $payment_type,
            'files_dir'    => empty($files_dir) ? NULL : trim($files_dir),
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        // Вставляем новое ТЗ в таблицу
        $this->db->insert('customer_specs', $data);

        // Возвращаем ID нового ТЗ
        return $this->db->insert_id();
    }

    /**
     * Обновить существующее ТЗ
     *
     * @param int         $spec_id      ID ТЗ
     * @param string      $title        Название ТЗ
     * @param string      $content      HTML-текст ТЗ
     * @param float       $price        Ценник ТЗ
     * @param float       $prepayment   Предоплата ТЗ
     * @param string      $payment_type Тип оплаты ТЗ (fixed/hourly)
     * @param string|null $files_dir    Путь к внешней директории с файлами
     * @return bool Результат выполнения UPDATE
     */
    public function update_spec($spec_id, $title, $content, $price = 0.00, $prepayment = 0.00, $payment_type = 'hourly', $files_dir = NULL)
    {
        // Формируем массив обновляемых полей
        $data = [
            'title'        => $title,
            'content'      => $content,
            'price'        => (float)$price,
            'prepayment'   => (float)$prepayment,
            'payment_type' => $payment_type,
            'files_dir'    => empty($files_dir) ? NULL : trim($files_dir),
        ];

        // Фильтр по ID ТЗ
        $this->db->where('id', $spec_id);

        // Выполняем обновление
        return $this->db->update('customer_specs', $data);
    }

    /**
     * Удалить ТЗ (перед удалением отвязывает все задачи от этого ТЗ)
     *
     * @param int $spec_id ID ТЗ
     * @return bool Результат выполнения DELETE
     */
    public function delete_spec($spec_id)
    {
        // Отвязываем все задачи от ТЗ, чтобы они не ссылались на удалённое ТЗ
        $this->db->where('spec_id', $spec_id);
        $this->db->update('tasks', ['spec_id' => NULL]);

        // Удаляем запись ТЗ (файлы удалятся каскадно по FK)
        $this->db->where('id', $spec_id);
        return $this->db->delete('customer_specs');
    }

    /**
     * Получить список всех активных задач конкретного заказчика
     *
     * @param int $customer_id ID заказчика
     * @param int $user_id     ID авторизованного пользователя
     * @return array Список задач, отсортированных по дате создания ASC
     */
    public function get_customer_tasks($customer_id, $user_id)
    {
        // Фильтр по заказчику
        $this->db->where('customer_id', $customer_id);

        // Фильтр по пользователю — только свои задачи
        $this->db->where('user_id', $user_id);

        // Только активные задачи
        $this->db->where('status', 'active');

        // Только не удалённые задачи (мягкое удаление)
        $this->db->where('deleted_at IS NULL', NULL, FALSE);

        // Сортируем по дате создания — старые первые
        $this->db->order_by('created_at', 'ASC');

        // Возвращаем плоский список задач
        return $this->db->get('tasks')->result_array();
    }

    // =========================================================================
    // ПРИВЯЗКА/ОТВЯЗКА ЗАДАЧ К ТЗ
    // =========================================================================

    /**
     * Получить ID задач, привязанных к конкретному ТЗ.
     *
     * @param int $spec_id     ID ТЗ
     * @param int $customer_id ID заказчика (для проверки принадлежности)
     * @param int $user_id     ID пользователя (для фильтрации)
     * @return array Массив ID привязанных задач
     */
    public function get_linked_task_ids($spec_id, $customer_id, $user_id)
    {
        // Выбираем только ID задач для минимизации трафика
        $this->db->select('id');

        // Фильтр по ТЗ
        $this->db->where('spec_id', $spec_id);

        // Фильтр по заказчику — защита от подмены spec_id
        $this->db->where('customer_id', $customer_id);

        // Фильтр по пользователю — только свои задачи
        $this->db->where('user_id', $user_id);

        // Только не удалённые задачи
        $this->db->where('deleted_at IS NULL', NULL, FALSE);

        // Выполняем запрос
        $result = $this->db->get('tasks')->result_array();

        // Извлекаем плоский массив ID из результата
        return array_column($result, 'id');
    }

    /**
     * Привязать массив задач к конкретному ТЗ.
     *
     * Устанавливает spec_id у выбранных задач с проверкой принадлежности
     * к заказчику и пользователю.
     *
     * @param int   $spec_id     ID ТЗ для привязки
     * @param array $task_ids    Массив ID задач для привязки
     * @param int   $customer_id ID заказчика (для проверки)
     * @param int   $user_id     ID пользователя (для проверки)
     * @return bool Результат выполнения UPDATE
     */
    public function link_tasks_to_spec($spec_id, $task_ids, $customer_id, $user_id)
    {
        // Проверяем, что массив не пуст и является массивом
        if (empty($task_ids) || !is_array($task_ids)) {
            return false;
        }

        // Фильтруем по списку задач
        $this->db->where_in('id', $task_ids);

        // Проверяем принадлежность к заказчику
        $this->db->where('customer_id', $customer_id);

        // Проверяем принадлежность к пользователю
        $this->db->where('user_id', $user_id);

        // Устанавливаем spec_id у задач
        return $this->db->update('tasks', ['spec_id' => $spec_id]);
    }

    /**
     * Отвязать все задачи от конкретного ТЗ.
     *
     * Обнуляет spec_id у всех задач, привязанных к данному ТЗ.
     * Используется перед повторной привязкой (при редактировании ТЗ).
     *
     * @param int $spec_id     ID ТЗ
     * @param int $customer_id ID заказчика (для проверки)
     * @param int $user_id     ID пользователя (для проверки)
     * @return bool Результат выполнения UPDATE
     */
    public function unlink_tasks_from_spec($spec_id, $customer_id, $user_id)
    {
        // Фильтр по ТЗ — отвязываем только задачи этого ТЗ
        $this->db->where('spec_id', $spec_id);

        // Проверяем принадлежность к заказчику
        $this->db->where('customer_id', $customer_id);

        // Проверяем принадлежность к пользователю
        $this->db->where('user_id', $user_id);

        // Обнуляем spec_id — задача больше не привязана к ТЗ
        return $this->db->update('tasks', ['spec_id' => NULL]);
    }

    // =========================================================================
    // ПРОВЕРКА ПРАВ ВЛАДЕНИЯ
    // =========================================================================

    /**
     * Проверить, что ТЗ принадлежит указанному пользователю.
     *
     * Делает JOIN между customer_specs и customers, чтобы проверить
     * цепочку: spec → customer → user.
     *
     * @param int $spec_id ID ТЗ
     * @param int $user_id ID пользователя
     * @return bool TRUE если ТЗ принадлежит пользователю, FALSE иначе
     */
    public function verify_spec_ownership($spec_id, $user_id)
    {
        // Выбираем ID ТЗ — нам нужен только факт существования
        $this->db->select('customer_specs.id');

        // Присоединяем таблицу заказчиков для проверки владельца
        $this->db->join('customers', 'customers.id = customer_specs.customer_id', 'inner');

        // Фильтр по ID ТЗ
        $this->db->where('customer_specs.id', $spec_id);

        // Фильтр по владельцу заказчика
        $this->db->where('customers.user_id', $user_id);

        // Выполняем запрос
        $result = $this->db->get('customer_specs')->row_array();

        // Если запись найдена — ТЗ принадлежит пользователю
        return !empty($result);
    }

    /**
     * Проверить, что файл ТЗ принадлежит указанному пользователю.
     *
     * Делает двойной JOIN: file → spec → customer, чтобы проверить
     * полную цепочку владения.
     *
     * @param int $file_id ID файла
     * @param int $user_id ID пользователя
     * @return bool TRUE если файл принадлежит пользователю, FALSE иначе
     */
    public function verify_file_ownership($file_id, $user_id)
    {
        // Выбираем ID файла — нам нужен только факт существования
        $this->db->select('customer_spec_files.id');

        // Присоединяем таблицу ТЗ
        $this->db->join('customer_specs', 'customer_specs.id = customer_spec_files.spec_id', 'inner');

        // Присоединяем таблицу заказчиков для проверки владельца
        $this->db->join('customers', 'customers.id = customer_specs.customer_id', 'inner');

        // Фильтр по ID файла
        $this->db->where('customer_spec_files.id', $file_id);

        // Фильтр по владельцу заказчика
        $this->db->where('customers.user_id', $user_id);

        // Выполняем запрос
        $result = $this->db->get('customer_spec_files')->row_array();

        // Если запись найдена — файл принадлежит пользователю
        return !empty($result);
    }

    // =========================================================================
    // МЕТОДЫ РАБОТЫ С ФАЙЛАМИ ТЗ
    // =========================================================================

    /**
     * Получить список прикреплённых файлов для ТЗ
     *
     * @param int $spec_id ID ТЗ
     * @return array Список файлов ТЗ
     */
    public function get_spec_files($spec_id)
    {
        // Фильтр по ТЗ
        $this->db->where('spec_id', $spec_id);

        // Возвращаем все файлы ТЗ
        return $this->db->get('customer_spec_files')->result_array();
    }

    /**
     * Получить данные файла по его ID
     *
     * @param int $file_id ID файла
     * @return array|null Данные файла или null
     */
    public function get_spec_file($file_id)
    {
        // Фильтр по ID файла
        $this->db->where('id', $file_id);

        // Возвращаем одну запись
        return $this->db->get('customer_spec_files')->row_array();
    }

    /**
     * Добавить запись о файле или ссылке в базу данных
     *
     * @param int    $spec_id   ID ТЗ
     * @param string $filename  Уникальное имя файла на диске или URL-адрес ссылки
     * @param string $orig_name Оригинальное имя файла или название ссылки
     * @param int    $file_size Размер файла в байтах (0 для ссылок)
     * @param int    $is_link   Является ли запись ссылкой (1 — да, 0 — нет)
     * @return int ID добавленной записи
     */
    public function add_spec_file($spec_id, $filename, $orig_name, $file_size, $is_link = 0)
    {
        // Формируем массив данных для вставки
        $data = [
            'spec_id'    => $spec_id,
            'filename'   => $filename,
            'orig_name'  => $orig_name,
            'file_size'  => $file_size,
            'is_link'    => $is_link,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Вставляем запись о файле
        $this->db->insert('customer_spec_files', $data);

        // Возвращаем ID записи
        return $this->db->insert_id();
    }

    /**
     * Удалить запись о файле из базы данных
     *
     * @param int $file_id ID файла
     * @return bool Результат выполнения DELETE
     */
    public function delete_spec_file($file_id)
    {
        // Фильтр по ID файла
        $this->db->where('id', $file_id);

        // Удаляем запись
        return $this->db->delete('customer_spec_files');
    }
}
