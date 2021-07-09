<?php

/**
 * @author Mygento Team
 * @copyright 2017-2020 Mygento (https://www.mygento.ru)
 * @package Mygento_Kkm
 */

namespace Mygento\Kkm\Model\Source\Atol;

use Magento\Framework\Data\OptionSourceInterface;

class ErrorCode implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('System Errors'),
                'value' => [
                    ['value' => ErrorType::SYSTEM . '_1', 'label' => __('[1] - Сервер не смог обработать входной чек')],
                    ['value' => ErrorType::SYSTEM . '_10', 'label' => __('[10] - Не распознан токен запроса')],
                    ['value' => ErrorType::SYSTEM . '_11', 'label' => __('[11] - Переданный токен не активен')],
                    ['value' => ErrorType::SYSTEM . '_12', 'label' => __('[12] - Неверный логин или пароль')],
                    ['value' => ErrorType::SYSTEM . '_13', 'label' => __('[13] - Ошибка валидации входящего запроса')],
                    ['value' => ErrorType::SYSTEM . '_20', 'label' => __('[20] - Код группы, указанный в запросе, не соответствует токену')],
                    ['value' => ErrorType::SYSTEM . '_21', 'label' => __('[21] - Код группы не поддерживает данную версию протокола')],
                    ['value' => ErrorType::SYSTEM . '_30', 'label' => __('[30] - Передан некорректный UUID')],
                    ['value' => ErrorType::SYSTEM . '_31', 'label' => __('[31] - Операция не поддерживается')],
                    ['value' => ErrorType::SYSTEM . '_32', 'label' => __('[32] - Ошибка валидации входного чека')],
                    ['value' => ErrorType::SYSTEM . '_33', 'label' => __('[33] - В системе существует чек с external_id и group_code')],
                    ['value' => ErrorType::SYSTEM . '_34', 'label' => __('[34] - Состояние чека не найдено. Попробуйте позднее')],
                    ['value' => ErrorType::SYSTEM . '_40', 'label' => __('[40] - Некорректный запрос')],
                    ['value' => ErrorType::SYSTEM . '_41', 'label' => __('[41] - Некорректный ContentType')],
                    ['value' => ErrorType::SYSTEM . '_50', 'label' => __('[50] - Ошибка сервера. Обратитесь к Администратору')]
                ],
            ],
            [
                'label' => __('Timeout errors'),
                'value' => [
                    ['value' => ErrorType::TIMEOUT . '_1', 'label' => __('[1] - Превышено время ожидания чека в очереди')],
                ],
            ],
            [
                'label' => __('Cash machine errors: Agent Errors'),
                'value' => [
                    ['value' => ErrorType::AGENT . '_1', 'label' => __('[1] - Документ некорректен')],
                    ['value' => ErrorType::AGENT . '_2', 'label' => __('[2] - ККТ зарегистрирована с другим ИНН или адресом расчёта')],
                    ['value' => ErrorType::AGENT . '_3', 'label' => __('[3] - Дата/время ККТ не корректны')],
                    ['value' => ErrorType::AGENT . '_4', 'label' => __('[4] - Отсутствует возможность регистрации документа данного типа')],

                ],
            ],
            [
                'label' => __('Cash machine errors: Driver Errors'),
                'value' => [
                    ['value' => ErrorType::DRIVER . '_-1', 'label' => __('[-1] - Нет связи')],
                    ['value' => ErrorType::DRIVER . '_-6', 'label' => __('[-6] - Недопустимое значение')],
                    ['value' => ErrorType::DRIVER . '_-12', 'label' => __('[-12] - Не поддерживает в данной версии оборудования')],
                    ['value' => ErrorType::DRIVER . '_-15', 'label' => __('[-15] - Некорректные данные из устройства')],
                    ['value' => ErrorType::DRIVER . '_-16', 'label' => __('[-16] - Не поддерживается в данном режиме устройства')],
                    ['value' => ErrorType::DRIVER . '_-20', 'label' => __('[-20] - Нет ответа от устройства')],
                    ['value' => ErrorType::DRIVER . '_-21', 'label' => __('[-21] - Соединение разорвано')],
                    ['value' => ErrorType::DRIVER . '_-199', 'label' => __('[-199] - Неизвестная ошибка')],
                    ['value' => ErrorType::DRIVER . '_-3803', 'label' => __('[-3803] - Неверная цена (сумма)')],
                    ['value' => ErrorType::DRIVER . '_-3804', 'label' => __('[-3804] - Неверное количество')],
                    ['value' => ErrorType::DRIVER . '_-3805', 'label' => __('[-3805] - Нулевая цена')],
                    ['value' => ErrorType::DRIVER . '_-3809', 'label' => __('[-3809] - Недопустимый ИНН')],
                    ['value' => ErrorType::DRIVER . '_-3810', 'label' => __('[-3810] - Накопление меньше суммы возврата или аннулирования')],
                    ['value' => ErrorType::DRIVER . '_-3813', 'label' => __('[-3813] - Неверная величина скидки/надбавки')],
                    ['value' => ErrorType::DRIVER . '_-3816', 'label' => __('[-3816] - Неверный вид оплаты')],
                    ['value' => ErrorType::DRIVER . '_-3817', 'label' => __('[-3817] - Переполнение при умножении')],
                    ['value' => ErrorType::DRIVER . '_-3818', 'label' => __('[-3818] - Операция запрещена в таблице настроек')],
                    ['value' => ErrorType::DRIVER . '_-3819', 'label' => __('[-3819] - Переполнение итога чека')],
                    ['value' => ErrorType::DRIVER . '_-3822', 'label' => __('[-3822] - Смена превысила 24 часа')],
                    ['value' => ErrorType::DRIVER . '_-3823', 'label' => __('[-3823] - Скидка запрещена в таблице')],
                    ['value' => ErrorType::DRIVER . '_-3825', 'label' => __('[-3825] - Неверный пароль')],
                    ['value' => ErrorType::DRIVER . '_-3830', 'label' => __('[-3830] - Неверная дата')],
                    ['value' => ErrorType::DRIVER . '_-3831', 'label' => __('[-3831] - Неверное время')],
                    ['value' => ErrorType::DRIVER . '_-3835', 'label' => __('[-3835] - Вносимая сумма меньше суммы чека')],
                    ['value' => ErrorType::DRIVER . '_-3863', 'label' => __('[-3863] - Скидка/надбавка на предыдущую операцию невозможна')],
                    ['value' => ErrorType::DRIVER . '_-3895', 'label' => __('[-3895] - Неверная длина')],
                    ['value' => ErrorType::DRIVER . '_-3896', 'label' => __('[-3896] - Сумма не наличных оплат превышает сумму чека')],
                    ['value' => ErrorType::DRIVER . '_-3897', 'label' => __('[-3897] - Чек оплачен не полностью')],
                    ['value' => ErrorType::DRIVER . '_-3905', 'label' => __('[-3905] - Неверный формат или значение')],
                    ['value' => ErrorType::DRIVER . '_-3906', 'label' => __('[-3906] - Переполнение суммы платежей')],
                    ['value' => ErrorType::DRIVER . '_-3910', 'label' => __('[-3910] - Ошибка интерфейса ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3911', 'label' => __('[-3911] - Ошибка формата передачи параметров ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3912', 'label' => __('[-3912] - Неверное состояние ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3913', 'label' => __('[-3913] - Неисправимая ошибка ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3914', 'label' => __('[-3914] - Авария крипто–процессора ЭКЛЗ/Ошибка КС ФН')],
                    ['value' => ErrorType::DRIVER . '_-3915', 'label' => __('[-3915] - Исчерпан временной ресурс ЭКЛЗ/Закончен срок эксплуатации ФН')],
                    ['value' => ErrorType::DRIVER . '_-3916', 'label' => __('[-3916] - ЭКЛЗ переполнена/Архив ФН переполнен')],
                    ['value' => ErrorType::DRIVER . '_-3917', 'label' => __('[-3917] - В ЭКЛЗ/ФН переданы неверные дата и время')],
                    ['value' => ErrorType::DRIVER . '_-3919', 'label' => __('[-3919] - Переполнение итога чека в ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3922', 'label' => __('[-3922] - Дата и/или время в ККТ меньше чем в ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3924', 'label' => __('[-3924] - Необходимо провести профилактические работы')],
                    ['value' => ErrorType::DRIVER . '_-3929', 'label' => __('[-3929] - Переполнение счетчика наличности')],
                    ['value' => ErrorType::DRIVER . '_-3932', 'label' => __('[-3932] - Неверный тип чека')],
                    ['value' => ErrorType::DRIVER . '_-3933', 'label' => __('[-3933] - Вход в режим заблокирован')],
                    ['value' => ErrorType::DRIVER . '_-3934', 'label' => __('[-3934] - Неверные номер смен в ККТ и ЭКЛЗ/ФН')],
                    ['value' => ErrorType::DRIVER . '_-3935', 'label' => __('[-3935] - ЭКЛЗ/ФН отсутствует')],
                    ['value' => ErrorType::DRIVER . '_-3936', 'label' => __('[-3936] - Итоги чека ККТ и ЭКЛЗ/ФН не совпадают')],
                    ['value' => ErrorType::DRIVER . '_-3957', 'label' => __('[-3957] - Переполнение буфера чека')],
                    ['value' => ErrorType::DRIVER . '_-3965', 'label' => __('[-3965] - Недопустимый пароль')],
                    ['value' => ErrorType::DRIVER . '_-3969', 'label' => __('[-3969] - Сумма налога больше суммы регистраций по чеку и/или итога')],
                    ['value' => ErrorType::DRIVER . '_-3970', 'label' => __('[-3970] - Начисление налога на последнюю операцию невозможно')],
                    ['value' => ErrorType::DRIVER . '_-3975', 'label' => __('[-3975] - Некорректное значение параметров команды ФН')],
                    ['value' => ErrorType::DRIVER . '_-3976', 'label' => __('[-3976] - Превышение размеров TLV данных ФН')],
                    ['value' => ErrorType::DRIVER . '_-3978', 'label' => __('[-3978] - Исчерпан ресурс КС ФН')],
                    ['value' => ErrorType::DRIVER . '_-3979', 'label' => __('[-3979] - Исчерпан ресурс хранения ФН')],
                    ['value' => ErrorType::DRIVER . '_-3980', 'label' => __('[-3980] - Сообщение от ОФД не может быть принято ФН')],
                    ['value' => ErrorType::DRIVER . '_-4021', 'label' => __('[-4021] - Некорректная СНО')],
                    ['value' => ErrorType::DRIVER . '_-4022', 'label' => __('[-4022] - Недопустимый номер ставки налога')],
                    ['value' => ErrorType::DRIVER . '_-4025', 'label' => __('[-4025] - Ошибка программирования реквизита')],
                ],
            ],
            [
                'label' => __('Unknown Errors'),
                'value' => [
                    ['value' => ErrorType::UNKNOWN . '_0', 'label' => __('[0] - Неизвестная ошибка обработки')],
                ],
            ],
        ];
    }
}
