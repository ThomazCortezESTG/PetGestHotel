<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "raca".
 *
 * @property int $id
 * @property string $nome
 * @property string|null $descricao
 * @property string|null $tipo
 *
 * @property Animal[] $animals
 */
class Raca extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const TIPO_CAO = 'Cão';
    const TIPO_GATO = 'Gato';
    const TIPO_OUTROS = 'Outros';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'raca';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descricao', 'tipo'], 'default', 'value' => null],
            [['nome'], 'required'],
            [['tipo'], 'string'],
            [['nome'], 'string', 'max' => 100],
            [['descricao'], 'string', 'max' => 255],
            ['tipo', 'in', 'range' => array_keys(self::optsTipo())],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nome' => 'Nome',
            'descricao' => 'Descricao',
            'tipo' => 'Tipo',
        ];
    }

    /**
     * Gets query for [[Animals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAnimals()
    {
        return $this->hasMany(Animal::class, ['id_raca' => 'id']);
    }


    /**
     * column tipo ENUM value labels
     * @return string[]
     */
    public static function optsTipo()
    {
        return [
            self::TIPO_CAO => 'Cão',
            self::TIPO_GATO => 'Gato',
            self::TIPO_OUTROS => 'Outros',
        ];
    }

    /**
     * @return string
     */
    public function displayTipo()
    {
        return self::optsTipo()[$this->tipo];
    }

    /**
     * @return bool
     */
    public function isTipoCao()
    {
        return $this->tipo === self::TIPO_CAO;
    }

    public function setTipoToCao()
    {
        $this->tipo = self::TIPO_CAO;
    }

    /**
     * @return bool
     */
    public function isTipoGato()
    {
        return $this->tipo === self::TIPO_GATO;
    }

    public function setTipoToGato()
    {
        $this->tipo = self::TIPO_GATO;
    }

    /**
     * @return bool
     */
    public function isTipoOutros()
    {
        return $this->tipo === self::TIPO_OUTROS;
    }

    public function setTipoToOutros()
    {
        $this->tipo = self::TIPO_OUTROS;
    }
}
