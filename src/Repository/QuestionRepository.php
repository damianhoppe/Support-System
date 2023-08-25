<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 *
 * @method Question|null find($id, $lockMode = null, $lockVersion = null)
 * @method Question|null findOneBy(array $criteria, array $orderBy = null)
 * @method Question[]    findAll()
 * @method Question[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Question::class);
    }

    public function findAllByCategory(int $categoryId): array {
        return $this->createQueryBuilder('q')
            ->andWhere('q.category = :val')
            ->setParameter('val', $categoryId)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    
    public function search(string $query): array {
        
        $rsm = $this->createResultSetMappingBuilder('q');
        $select = $rsm->generateSelectClause(['q']);

        $querySql = "'" . $query . "'";
        $sql = <<<SQL
        SELECT $select FROM questions q WHERE MATCH (title, content) AGAINST ($querySql IN BOOLEAN MODE) > 0 ORDER BY MATCH (title, content) AGAINST ($querySql IN BOOLEAN MODE) DESC;
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        return $query->getResult();
    }
}
