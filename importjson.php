<?php
/**
 * @package    Import_JSON
 * @author     CoderSite <info@codersite.ru>
 * @copyright  Copyright (C) 2023 - 2023 codersite.ru. All rights reserved.
 * @license    GNU General Public License version 3 or later; see LICENSE
 */
//kill direct access
defined('_JEXEC') || die;


use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;


class PlgContentImportjson extends CMSPlugin {
    
    public function onExtensionAfterSave() { 
            $created_by = $this->params->get('created_by', '1'); // автор материалов
            $catid = $this->params->get('catid', '2'); //в какую категорию импортировать
            $state = $this->params->get('state', '1'); // статус публикации
            $url = $this->params->get('url', '1'); // ссылка на JSON

       function addArticle($created_by,$catid,$state,$title,$description,$images,$phone,$fulladdress,$region,$city,$street,$house,$coordinate,$uslugi,$site,$brand,$mail) {

            $article = Table::getInstance('content');

            $article->title = $title; // Добавляем заголовок
            $article->alias = ""; // Создаем алиас из заголовка
            $article->introtext = $description; // Добавляем вступительный текст
            $article->fulltext = $description; // Добавляем полный текст
            $article->created_by = $created_by; //  Указываем автора
            $article->modified_by = $created_by; // Также указываем того, кто вносил изменения. Это один и тот же пользователь

            $article->metadesc = $description; // Мета-тег Description
            $article->images = '{"image_intro":"'.$images.'","image_intro_alt":"Автосалон '.$title.'","float_intro":"","image_intro_caption":"","image_fulltext":"'.$images.'","image_fulltext_alt":"Автосалон '.$title.'","float_fulltext":"","image_fulltext_caption":""}';
            //Изображения


            // Joomla 4
            $article->catid = $catid ; // id категории
            $article->metadata = '{"robots":"","author":"","rights":""}';


            $article->created = Factory::getDate()->toSQL(); // Дата создания
            $article->state = $state; // Статус
            $article->access = 1; // Доступ разрешен
            $article->language = '*'; // Языки: все

            // Проверка данных на корректность
            if (!$article->check()) {
                echo 'Отправленные данные не прошли проверку';
                die();
            }

            // Запись в базу данных
            if (!$article->store(TRUE)) {
                echo "<script>alert('Запись в базу данных завершилась с ошибкой');</script>";
                die();
            }
            echo "<script>alert('Запись в базу данных успешно завершилась');</script>";
            // Далее фрагмент, который необходимо запускать исключительно под Joomla 4
            // Если не добавить запись в таблицу workflow_associations
            // то в административной панели в списке статей статья будет не видна

            $object = new stdClass();
            $object->item_id = $article->id;
            $object->stage_id = 1;
            $object->extension = 'com_content.article';

            Factory::getDbo()->insertObject('#__workflow_associations', $object);

            

            //Добавляем пользовательские поля


            // Регистрируем класс FieldsHelper.
            JLoader::register('FieldsHelper', JPATH_ADMINISTRATOR . '/components/com_fields/helpers/fields.php');

            // Добавляем путь поиска классов модели.
            BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_fields/models', 'FieldsModel');

            // Инициализируем объект модели класса FieldsModelField.
            $model = BaseDatabaseModel::getInstance('Field', 'FieldsModel', array('ignore_request' => true));

            $fields = [
                11 => $phone, // телефон
                15 => $fulladdress, // полный адрес
                4 => $region, // регион
                5 => $city, // город
                6 => $street, // улица
                7 => $house, // номер дома
                8 => $coordinate, // координаты
                9 => $site, // координаты
                3 => $mail, // mail
                13 => explode( ',', $uslugi), // услуги
                14 => explode( ',', $brand), // Марки
            ];

            foreach ($fields as $key => $value)
            {
                $model->setFieldValue($key, $article->id, $value);
            }
            //Закончили добавлять пользовательские поля
                
        }

        if ($this->params->get('start') == 1) {


            $jsonArray = [];
         
            $header_response = get_headers($url);

            if ($header_response) {
            if ( strpos( $header_response[0], "404" ) !== false ){
            // PDF DOES NOT EXIST
            echo "Такой страницы не существует";
            }else{
            // PDF EXISTS!!
                $json = file_get_contents($url);
                $jsonArray = json_decode($json, true);

                // echo "<pre>";
                // print_r($jsonArray);
                // echo "</pre>";
                foreach ($jsonArray as $item) {
                    //echo $item['Name'];
                    addArticle($created_by,$catid,$state,$item['title'],$item['description'],$item['images'],$item['phone'],$item['fulladdress'],$item['region'],$item['city'],$item['street'],$item['house'],$item['coordinate'],$item['uslugi'],$item['site'],$item['brand'],$item['mail']);
                }
            }
            }else {
            echo "Поле пусто";
            }
        }
   }
}