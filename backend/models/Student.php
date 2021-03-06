<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "student".
 *
 * @property string $wechat_id
 * @property string $stu_id
 * @property integer $school_id
 * @property string $dorm
 * @property integer $grade
 * @property string $mail
 * @property integer $created_at
 * @property string $updated_at
 *
 * @property School $school
 * @property User $wechat
 */
class Student extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'student';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            //[['wechat_id', 'school_id', 'dorm', 'grade', 'created_at'], 'required'],
            [['school_id', 'grade', 'created_at'], 'integer'],
            [['updated_at'], 'safe'],
            [['wechat_id', 'dorm', 'mail'], 'string', 'max' => 45],
            [['stu_id'], 'string', 'max' => 12]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'wechat_id' => '微信',
            'stu_id' => '编号',
            'school_id' => '学校',
            'dorm' => '寝室',
            'grade' => '年级',
            'mail' => '邮箱',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSchool()
    {
        return $this->hasOne(School::className(), ['school_id' => 'school_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWechat()
    {
        return $this->hasOne(User::className(), ['wechat_id' => 'wechat_id']);
    }
}
