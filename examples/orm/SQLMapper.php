<?php

/*
 * Essentially this class is a placeholder for our eventual SQL handling capability.
 */

require_once('A/Orm/DataMapper.php');
require_once('A/Orm/DataMapper/Mapping.php');

class I_Was_Told_To_Call_This_A_Table_Data_Gateway_But_The_Name_Really_Doesnt_Matter_Because_Its_Just_A_Placeholder_For_The_SQL_Functionality_Of_The_ORM extends A_Orm_DataMapper	{

	public function getById($id)	{
		$stmt = $this->db->prepare('SELECT ' . join(', ', $this->getColumns()) . ' FROM ' . $this->table . ' WHERE id = :id');
		$stmt->bindValue (':id', $id);
		$stmt->execute();
		return $this->load($stmt->fetch(PDO::FETCH_ASSOC));
	}

	public function getAll()	{
		$stmt = $this->db->prepare('SELECT ' . join(', ', $this->getColumns()) . ' FROM ' . $this->table);
		$stmt->execute();
		$posts = array();
		foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $post)	{
			$posts[$post['id']] = $this->load($post);
		}
		return $posts;
	}

	public function save($object)	{
		if ($user->getId())	{ // should we have a way to get the key column using mappings, rather than assuming getId() here?
			$this->update($object);
		} else {
			$this->insert($object);
		}
	}

	public function insert($object)	{
		$keys = array();
		$values = array();
		foreach ($this->getValues($object) as $key => $value)	{
			$keys[] = $key . ' = ?';
			$values[] = $value;
		}
		$stmt = $this->db->prepare ('INSERT INTO ' . $this->table . ' SET ' . join(',', $keys));
		$stmt->execute($values);
		// Somehow update $object with ID
	}

	public function update($object)	{
		$keys = array();
		$values = array();
		foreach ($this->getValues($object) as $key => $value)	{
			$keys[] = $key . ' = ?';
			$values[] = $value;
		}
		$pkey = $this->getKey($object);
		$values[] = current($pkey);

		$stmt = $this->db->prepare ('UPDATE ' . $this->table . ' SET ' . join(',', $keys) . ' WHERE ' . key($pkey) . ' = ?');
		$stmt->execute($values);
	}

}