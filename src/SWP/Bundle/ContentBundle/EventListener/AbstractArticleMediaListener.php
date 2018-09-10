<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Content Bundle.
 *
 * Copyright 2018 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2018 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\ContentBundle\EventListener;

use SWP\Bundle\ContentBundle\Doctrine\ArticleMediaRepositoryInterface;
use SWP\Bundle\ContentBundle\Doctrine\ORM\ArticleMediaRepository;
use SWP\Bundle\ContentBundle\Factory\MediaFactoryInterface;
use SWP\Bundle\ContentBundle\Model\ArticleInterface;
use SWP\Bundle\ContentBundle\Model\ArticleMediaInterface;
use SWP\Bundle\ContentBundle\Processor\ArticleBodyProcessorInterface;
use SWP\Component\Bridge\Model\ItemInterface;

abstract class AbstractArticleMediaListener
{
    /**
     * @var ArticleMediaRepository
     */
    protected $articleMediaRepository;

    /**
     * @var MediaFactoryInterface
     */
    protected $mediaFactory;

    /**
     * @var ArticleBodyProcessorInterface
     */
    protected $articleBodyProcessor;

    public function __construct(
        ArticleMediaRepositoryInterface $articleMediaRepository,
        MediaFactoryInterface $mediaFactory,
        ArticleBodyProcessorInterface $articleBodyProcessor
    ) {
        $this->articleMediaRepository = $articleMediaRepository;
        $this->mediaFactory = $mediaFactory;
        $this->articleBodyProcessor = $articleBodyProcessor;
    }

    public function handleMedia(ArticleInterface $article, string $key, ItemInterface $item): ArticleMediaInterface
    {
        $articleMedia = $this->mediaFactory->create($article, $key, $item);
        foreach ($articleMedia->getRenditions() as $rendition) {
            $this->articleMediaRepository->persist($rendition);
        }

        $this->articleBodyProcessor->process($article, $articleMedia);

        if (ArticleInterface::KEY_FEATURE_MEDIA === $key) {
            $article->setFeatureMedia($articleMedia);
        }

        return $articleMedia;
    }

    public function removeOldArticleMedia(ArticleInterface $article): void
    {
        $existingArticleMedia = $this->articleMediaRepository->findBy([
            'article' => $article->getId(),
        ]);

        foreach ($existingArticleMedia as $articleMedia) {
            $this->articleMediaRepository->remove($articleMedia);
            if ($articleMedia === $article->getFeatureMedia()) {
                $article->setFeatureMedia(null);
            }
        }
    }

    public function removeArticleMediaIfNeeded($key, ArticleInterface $article): void
    {
        $existingArticleMedia = $this->articleMediaRepository->findOneBy([
            'key' => $key,
            'article' => $article->getId(),
        ]);

        if (null !== $existingArticleMedia) {
            $this->articleMediaRepository->remove($existingArticleMedia);
        }
    }
}
