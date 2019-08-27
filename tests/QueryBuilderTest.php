<?php

use PHPUnit\Framework\TestCase;

require dirname(__FILE__).'/../QueryBuilder.class.php';

final class QueryBuilderTest extends TestCase
{

    public function getBuilder($softDelete = false): QueryBuilder
    {
        return new QueryBuilder(null, 'id', $softDelete);
    }

    public function testSimpleQuery()
    {
        $q = $this->getBuilder()->from("utentes", "u")->toSQL();
        $this->assertEquals("SELECT * FROM utentes u", $q);
    }

    public function testOrderBy()
    {
        $q = $this->getBuilder()->from("utentes", "u")->orderBy("id", "DESC")->toSQL();
        $this->assertEquals("SELECT * FROM utentes u ORDER BY id DESC", $q);
    }

    public function testMultipleOrderBy()
    {
        $q = $this->getBuilder()
          ->from("utentes")
          ->orderBy("id", "ezaearz")
          ->orderBy("nome", "desc")
          ->toSQL();
        $this->assertEquals("SELECT * FROM utentes ORDER BY id, nome DESC", $q);
    }

    public function testLimit()
    {
        $q = $this->getBuilder()
          ->from("utentes")
          ->limit(10)
          ->orderBy("id", "DESC")
          ->toSQL();
        $this->assertEquals("SELECT * FROM utentes ORDER BY id DESC LIMIT 10", $q);
    }

    public function testOffset()
    {
        $q = $this->getBuilder()
          ->from("utentes")
          ->limit(10)
          ->offset(3)
          ->orderBy("id", "DESC")
          ->toSQL();
        $this->assertEquals("SELECT * FROM utentes ORDER BY id DESC LIMIT 10 OFFSET 3", $q);
    }

    public function testPage()
    {
        $q = $this->getBuilder()
          ->from("utentes")
          ->limit(10)
          ->page(3)
          ->orderBy("id", "DESC")
          ->toSQL();
        $this->assertEquals("SELECT * FROM utentes ORDER BY id DESC LIMIT 10 OFFSET 20", $q);
        $q = $this->getBuilder()
          ->from("utentes")
          ->limit(10)
          ->page(1)
          ->orderBy("id", "DESC")
          ->toSQL(true);
        $this->assertEquals("SELECT * FROM utentes ORDER BY id DESC LIMIT 10 OFFSET 0", $q);
    }

    public function testCondition()
    {
        $q = $this->getBuilder()
          ->from("utentes")
          ->where("id", 3, ">")
          ->limit(10)
          ->orderBy("id", "DESC")
          ->toSQL(true);
        $this->assertEquals("SELECT * FROM utentes WHERE id > 3 ORDER BY id DESC LIMIT 10", $q);
    }

    
    public function testMultipleCondition()
    {
        $q = $this->getBuilder()
          ->from("utentes")
          ->where("id", 3, '>')
          ->where("nome", 'Nelson', 'LIKE')
          ->limit(10)
          ->orderBy("id", "DESC")
          ->toSQL(true);
        $this->assertEquals("SELECT * FROM utentes WHERE id > 3 AND nome LIKE 'Nelson' ORDER BY id DESC LIMIT 10", $q);
    }

    public function testSelect()
    {
        $q = $this->getBuilder()
          ->select("id", "nome", "tipoinscricao")
          ->from("utentes");
        $this->assertEquals("SELECT id, nome, tipoinscricao FROM utentes", $q->toSQL());
    }

    public function testSelectMultiple()
    {
        $q = $this->getBuilder()
          ->select("id", "nome")
          ->from("utentes")
          ->select('tipoinscricao');
        $this->assertEquals("SELECT id, nome, tipoinscricao FROM utentes", $q->toSQL());
    }

    public function testSelectAsArray()
    {
        $q = $this->getBuilder()
          ->select(["id", "nome", "tipoinscricao"])
          ->from("utentes");
        $this->assertEquals("SELECT id, nome, tipoinscricao FROM utentes", $q->toSQL());
    }

    public function testGet()
    {
        $id = $this->getBuilder()
          ->from("utentes")
          ->where("nome", "Consumidor Final")
          ->get("id");
        $this->assertEquals("1", $id);
    }

    public function testGetObj()
    {
        $id = $this->getBuilder()
          ->from("utentes")
          ->where("nome", "Consumidor Final")
          ->get();
        $this->assertEquals("1", $id->id);
    }

    public function testAll()
    {
        $id = $this->getBuilder()
          ->from("utentes")
          ->all();
        $this->assertEquals(1, $id[0]->id);
        $this->assertEquals(4, count($id));
    }

    public function testFetchWithInvalidRow()
    {
        $id = $this->getBuilder()
          ->from("utentes")
          ->where("nome", "Nelson")
          ->get("id");
        $this->assertNull($id);
    }

    public function testCount()
    {
        $query = $this->getBuilder()
          ->from("utentes")
          ->where("id", [1, 2]);
        $this->assertEquals(2, $query->count());
    }

    public function testCountWithLimit()
    {
        $query = $this->getBuilder()
          ->from("utentes")
          ->limit(1)
          ->where("id", [1, 2]);
        $this->assertEquals(2, $query->count());
    }

    public function testBugCount()
    {
        $q = $this->getBuilder()->from("utentes");
        $this->assertEquals(4, $q->count());
        $this->assertEquals("SELECT * FROM utentes", $q->toSQL());
    }

    public function testJoin()
    {
        $q = $this->getBuilder()
            ->select('u.id', 'ui.instalacao')
            ->from('utentes', 'u')
            ->join('utentes_instalacoes ui', 'ui.utente = u.id')
            ->where('u.id', 4)
            ->toSql(true);
        $this->assertEquals("SELECT u.id, ui.instalacao FROM utentes u INNER JOIN utentes_instalacoes ui ON ui.utente = u.id WHERE u.id = 4", $q);
    }

    public function testLeftJoin()
    {
        $q = $this->getBuilder()
            ->select('u.id', 'ui.instalacao')
            ->from('utentes', 'u')
            ->join('utentes_instalacoes ui', 'ui.utente = u.id', 'left')
            ->where('u.id', 4)
            ->toSql(true);
        $this->assertEquals("SELECT u.id, ui.instalacao FROM utentes u LEFT JOIN utentes_instalacoes ui ON ui.utente = u.id WHERE u.id = 4", $q);
    }

    public function testErrorJoin()
    {
        $q = $this->getBuilder()
            ->from('utentes', 'u')
            ->join('utentes_instalacoes ui', 'ui.utente = u.id', 'blah')
            ->where('u.id', 4)
            ->toSQL(true);
        $this->assertEquals("SELECT * FROM utentes u INNER JOIN utentes_instalacoes ui ON ui.utente = u.id WHERE u.id = 4", $q);
    }

    public function testMultipleJoins() 
    {
        $q = $this->getBuilder()
            ->select('u.id', 'ui.instalacao')
            ->from('utentes', 'u')
            ->join('utentes_instalacoes ui', 'ui.utente = u.id')
            ->join('utente_eventos ue', 'ue.utente = u.id', 'left')
            ->where('u.id', 4)
            ->toSql(true);
        $this->assertEquals("SELECT u.id, ui.instalacao FROM utentes u INNER JOIN utentes_instalacoes ui ON ui.utente = u.id LEFT JOIN utente_eventos ue ON ue.utente = u.id WHERE u.id = 4", $q);
    }

    public function testGroupBy()
    {
        $q = $this->getBuilder()
            ->from('utentes')
            ->groupBy('nome')
            ->toSql();
        $this->assertEquals("SELECT * FROM utentes GROUP BY nome", $q);
    }

    public function testMultipleGroupBy()
    {
        $q = $this->getBuilder()
            ->from('utentes')
            ->groupBy('nome', 'id')
            ->toSql();
        $this->assertEquals("SELECT * FROM utentes GROUP BY nome, id", $q);
    }

    public function testMultipleGroupByArray()
    {
        $q = $this->getBuilder()
            ->from('utentes')
            ->groupBy(['nome', 'id'])
            ->toSql();
        $this->assertEquals("SELECT * FROM utentes GROUP BY nome, id", $q);
    }

    public function testDelete() 
    {
        $q = $this->getBuilder()
            ->from('utentes')
            ->where('tipoinscricao', 11)
            ->where('telemovel', 914957444)
            ->deleteSQL(null, true);
        $this->assertEquals("DELETE FROM utentes WHERE tipoinscricao = 11 AND telemovel = 914957444", $q);
    }

    public function testDeleteId() 
    {
        $q = $this->getBuilder()
            ->from('utentes')
            ->deleteSQL(3, true);
        $this->assertEquals("DELETE FROM utentes WHERE id = 3", $q);
    }

    public function testDeleteIdOverideWhere() 
    {
        $q = $this->getBuilder()
            ->from('utentes')
            ->where('tipoinscricao', 11)
            ->where('telemovel', 914957444)
            ->deleteSQL(3, true);
        $this->assertEquals("DELETE FROM utentes WHERE id = 3", $q);
    }

    public function testSoftDelete() 
    {
        $q = $this->getBuilder(true)
        ->from('utentes')
        ->deleteSQL(3, true);
        $this->assertEquals("UPDATE utentes SET deleted = NOW() WHERE id = 3", $q);
    }

    // public function testGetAllWithoutDeleted()
    // {

    // }

    // public function testGetAllWithDeleted()
    // {

    // }

    public function testUpdate()
    {
        $q = $this->getBuilder()
        ->from('utentes')
        ->where('tipoinscricao', 0)
        ->updateSql(['datanascimento' => date('Y-m-d'), 'tipoinscricao' => 2], null, true);
        $this->assertEquals("UPDATE utentes SET datanascimento = '".date('Y-m-d')."', tipoinscricao = 2 WHERE tipoinscricao = 0", $q);
    }

    public function testUpdateOverideID()
    {
        $q = $this->getBuilder()
        ->from('utentes')
        ->where('tipoinscricao', 0)
        ->updateSql(['datanascimento' => date('Y-m-d'), 'tipoinscricao' => 2], 3, true);
        $this->assertEquals("UPDATE utentes SET datanascimento = '".date('Y-m-d')."', tipoinscricao = 2 WHERE id = 3", $q);
    }

    public function testInsert()
    {
        $q = $this->getBuilder()
        ->from('utentes')
        ->insertSql(['nome' => 'Nelson', 'last' => 'Costa'], true);
        $this->assertEquals("INSERT INTO utentes (nome, last) VALUE ('Nelson', 'Costa')", $q);
    }


    public function testValidInsertAndDelete()
    {
        $q = $this->getBuilder()->from('utentes')->insertSQL(['nome' => 'Nelson'], true);
        $this->assertEquals("INSERT INTO utentes (nome) VALUE ('Nelson')", $q);
        
        $newId = $this->getBuilder()->from('utentes')->insert(['nome' => 'Nelson']);
        $this->assertIsInt($newId);
        
        $row = $this->getBuilder()->from('utentes')->where('id', $newId)->get();
        $this->assertEquals("Nelson", $row->nome);

        $rowsDeleted = $this->getBuilder()->from('utentes')->where('nome', 'Nelson')->delete();
        $this->assertEquals(1, $rowsDeleted);
    }

    public function testBugInsert()
    {
        $newId = $this->getBuilder()->from('utentes')->insert(['nome' => 'Nelson', 'last' => 'Costa']);
        $this->assertNull($newId);
        
        $row = $this->getBuilder()->from('utentes')->where('nome', 'Nelson')->get();
        $this->assertNull($row);
    }

    public function testBeforeInsert()
    {
        $sql = (new UtenteTest)->insertSQL(['nome' => 'Nelson'], true);
        $this->assertEquals("INSERT INTO utentes (nome) VALUE ('Nelson')", $sql);

        $newId = (new UtenteTest)->insert(['nome' => 'Nelson']);
        $this->assertIsInt($newId);

        $sql = (new UtenteTest)->where('id', $newId)->toSQL(true, true);
        $this->assertEquals("SELECT * FROM utentes WHERE id = $newId", $sql);

        $nome = (new UtenteTest)->where('id', $newId)->get('nome');
        $this->assertEquals('Nelson TEST!!', $nome);

        $sql = (new UtenteTest)->deleteSQL($newId, true);
        $this->assertEquals("DELETE FROM utentes WHERE id = $newId", $sql);

        (new UtenteTest)->delete($newId);
        $obj = (new UtenteTest)->where('id', $newId)->get();
        $this->assertNull($obj);
    }

    public function testAfterInsert()
    {
        $newId = (new UtenteTest)->insert(['nome' => 'Nelson']);

        $dataNascimento = (new UtenteTest)->where('id', $newId)->get('datanascimento');
        $this->assertEquals(date('Y-m-d'), $dataNascimento);
    }

    public function testBeforeUpdate()
    {
        $newId = (new UtenteTest)->insert(['nome' => 'Nelson']);

        $rowsUpdated = (new UtenteTest)->update(['nome' => 'Nelson1'], $newId);
        $this->assertEquals(1, $rowsUpdated);

        $nome = (new UtenteTest)->where('id', $newId)->get('nome');
        $this->assertEquals('Nelson1 TEST!!', $nome);

        (new UtenteTest)->delete($newId);
        $obj = (new UtenteTest)->where('id', $newId)->get();
        $this->assertNull($obj);
    }

    public function testAfterUpdate()
    {
        $newId = (new UtenteTest)->insert(['nome' => 'Nelson']);

        $rowsUpdated = (new UtenteTest)->update(['nome' => 'Nelson1'], $newId);
        $this->assertEquals(1, $rowsUpdated);

        $dataNascimento = (new UtenteTest)->where('id', $newId)->get('datanascimento');
        $this->assertEquals(date('Y-m-d'), $dataNascimento);

        (new UtenteTest)->delete($newId);
        $obj = (new UtenteTest)->where('id', $newId)->get();
        $this->assertNull($obj);
    }

    public function testOrWhere()
    {
        $sql = $this->getBuilder()
        ->from('utentes')
        ->where('id', 1)
        ->orWhere('id', 2)
        ->toSql(true);

        $this->assertEquals("SELECT * FROM utentes WHERE id = 1 OR id = 2", $sql);
    }

    public function testWhereGroupStart()
    {
        $sql = $this->getBuilder()
          ->from('utentes')
          ->whereGroupStart()
          ->where('id', 1)
          ->orWhere('id', 2)
          ->whereGroupEnd()
          ->where('datanascimento', '0000-00-00', 'LIKE')
          ->whereGroupStart()
          ->where('id', 3)
          ->orWhere('id', 4)
          ->whereGroupEnd()
          ->toSql(true);
        $this->assertEquals("SELECT * FROM utentes WHERE (id = 1 OR id = 2) AND datanascimento LIKE '0000-00-00' AND (id = 3 OR id = 4)", $sql);

        $sql = $this->getBuilder()
          ->from('utentes')
          ->whereGroupStart()
          ->where('id', 1)
          ->orWhere('id', 2)
          ->whereGroupEnd()
          ->where('datanascimento', '0000-00-00', 'LIKE')
          ->whereGroupStart()
          ->orWhere('id', 3)
          ->orWhere('id', 4)
          ->whereGroupEnd()
          ->toSql(true);
        $this->assertEquals("SELECT * FROM utentes WHERE (id = 1 OR id = 2) AND datanascimento LIKE '0000-00-00' OR (id = 3 OR id = 4)", $sql);
    }

    public function testObject()
    {
        $utente = new UtenteTest();
        $utente->nome = "Nelson";
        $utente->datanascimento = "1988-04-26";

        $this->assertEquals([
          'nome' => 'Nelson',
          'datanascimento' => '1988-04-26'
        ], $utente->toArray());


    }

    public function testSaveInsert()
    {
        $utente = new UtenteTest();

        $utente->nome = "Nelson";
        $utente->datanascimento = date('Y-m-d');
        $newId = $utente->save();

        $dataNascimento = (new UtenteTest)->where('id', $newId)->get('datanascimento');
        $this->assertEquals(date('Y-m-d'), $dataNascimento);

        (new UtenteTest)->delete($newId);
    }

    public function testSaveUpdate()
    {
        $utente = new UtenteTest();

        $utente->nome = "Nelson";
        $utente->datanascimento = date('Y-m-d');
        $newId = $utente->save();

        $obj = (new UtenteTest)->where('id', $newId)->get();
        $obj->nome = "DIGIMON!";
        $obj->save();

        $this->assertEquals("DIGIMON! TEST!!", (new UtenteTest)->where('id', $newId)->get("nome"));

        (new UtenteTest)->delete($newId);
    }

    public function testObjDelete()
    {
        $utente = new UtenteTest();
        $utente->nome = "Nelson";
        $utente->datanascimento = date('Y-m-d');
        $newId = $utente->save();

        $obj = (new UtenteTest)->where('id', $newId)->get();
        $obj->delete();

        $this->assertNull((new UtenteTest)->where('id', $newId)->get());
    }

    public function testObjInsertAndUpdate()
    {
        $utente = new UtenteTest();
        $utente->nome = "Nelson";
        $utente->save();

        $utente->datanascimento = '1988-04-26';
        $utente->save();
        $this->assertNotNull($utente->id);
        $this->assertEquals("Nelson TEST!! TEST!!", $utente->nome);
        $this->assertEquals("0", $utente->tipoinscricao);

        $utente->delete();
        $this->assertNull($utente->where('id', $utente->id)->get());
    }

}

class UtenteTest extends QueryBuilder {

    protected $table = 'utentes';

    protected function beforeInsert(&$params)
    {
        if($params['nome']) $params['nome'] .= ' TEST!!';
    }

    protected function afterInsert($params, $insertedId)
    {
        $this->update(['datanascimento' => date('Y-m-d')], $insertedId, false);
    }

    protected function beforeUpdate(&$params, $id)
    {
        if(!empty($params['nome'])) $params['nome'] .= ' TEST!!';
    }

    protected function afterUpdate($params, $id)
    {
        $this->update(['datanascimento' => date('Y-m-d')], $id, false);
    }
}
