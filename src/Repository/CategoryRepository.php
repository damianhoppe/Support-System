<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository {
    private LoggerInterface $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger) {
        parent::__construct($registry, Category::class);
        $this->logger = $logger;
    }

    public function findAllByParentCategory(int $parentCategoryId = null): array {
        if($parentCategoryId == null) {
            return $this->createQueryBuilder('c')
                ->andWhere('c.parentCategory is NULL')
                ->getQuery()
                ->getResult();
        }
        return $this->createQueryBuilder('c')
            ->andWhere('c.parentCategory = :val')
            ->setParameter('val', $parentCategoryId)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAll($localizedPreferences = null): array {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parentCategory = :val')
            ->setParameter('val', $parentCategoryId)
            // ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function categoriesTree(int $categoryId): array {
        
        $rsm = $this->createResultSetMappingBuilder('cte');
        $select = $rsm->generateSelectClause(['cte']);

        $sql = <<<SQL
        with recursive cte (id, title, icon, label, url, parent_category_id, ord) as 
        (
        select id, title, icon, label, url, parent_category_id, 1 as ord
        from  categories
        where id = $categoryId
        union all
        select c.id, c.title, c.icon, c.label, c.url, c.parent_category_id, t.ord+1
        from categories c join cte t
        on t.parent_category_id = c.id 
        )
        select $select from cte ORDER BY ord DESC;
        SQL;

        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        return $query->getResult();
    }

    public function findOneByUrl($url): ?Category {
        return $this->createQueryBuilder('c')
            ->andWhere('c.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
