<?php

namespace frontend\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Animal;

/**
 * PetSearch represents the model behind the search form of `common\models\Animal`.
 */
class PetSearch extends Animal
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'id_user', 'id_raca_principal', 'id_raca_secundaria'], 'integer'],
            [['nome', 'data', 'vacinas', 'observacoes'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
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
     * @param string|null $formName Form name to be used into `->load()` method.
     *
     * @return ActiveDataProvider
     */
    public function search($params, $formName = null)
    {
        // Cada cliente só vê os seus próprios animais
        $query = Animal::find()
            ->with(['racaPrincipal', 'racaSecundaria', 'documentos'])
            ->where(['id_user' => \Yii::$app->user->id])
            ->orderBy(['nome' => SORT_ASC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 24],
            'sort' => false,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'data' => $this->data,
            'id_raca_principal' => $this->id_raca_principal,
            'id_raca_secundaria' => $this->id_raca_secundaria,
        ]);

        $query->andFilterWhere(['like', 'nome', $this->nome])
            ->andFilterWhere(['like', 'vacinas', $this->vacinas])
            ->andFilterWhere(['like', 'observacoes', $this->observacoes]);

        return $dataProvider;
    }
}
