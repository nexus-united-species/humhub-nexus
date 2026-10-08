<?php

namespace nexus\modules\protectedLibrary\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $book_id
 * @property int $sort_order
 * @property string $title
 * @property string $html_content
 */
class Chapter extends ActiveRecord
{
    public static function tableName()
    {
        return 'nexus_book_chapter';
    }

    public function rules()
    {
        return [
            [['book_id', 'sort_order'], 'required'],
            [['book_id', 'sort_order'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['html_content'], 'string'],
        ];
    }

    public function getBook()
    {
        return $this->hasOne(Book::class, ['id' => 'book_id']);
    }
}
