<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Loan;

/**
 * LoanSearch represents the model behind the search form about `backend\models\Loan`.
 */
class LoanSearch extends Loan
{
    public $school;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['loan_id', 'money', 'duration', 'status', 'start_at', 'end_at', 'created_at'], 'integer'],
            [['wechat_id', 'school', 'reviewer', 'updated_at'], 'safe'],
            [['rate'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Loan::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pagesize' => '50',
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'loan_id' => $this->loan_id,
            'money' => $this->money,
            'duration' => $this->duration,
            'rate' => $this->rate,
            'status' => $this->status,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'wechat_id', $this->wechat_id])
            ->andFilterWhere(['like', 'reviewer', $this->reviewer]);

        return $dataProvider;
    }
}
