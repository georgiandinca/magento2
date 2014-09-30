<?php
/**
 *
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Model\Exception;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Model\UrlFinderInterface;

class Save extends \Magento\UrlRewrite\Controller\Adminhtml\Url\Rewrite
{
    /** @var \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator */
    protected $productUrlPathGenerator;

    /** @var \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator */
    protected $categoryUrlPathGenerator;

    /** @var \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator */
    protected $cmsPageUrlPathGenerator;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator
     * @param \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator $cmsPageUrlPathGenerator
     * @param UrlFinderInterface $urlFinder
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator $productUrlPathGenerator,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\CmsUrlRewrite\Model\CmsPageUrlPathGenerator $cmsPageUrlPathGenerator,
        UrlFinderInterface $urlFinder
    ) {
        parent::__construct($context);
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->cmsPageUrlPathGenerator = $cmsPageUrlPathGenerator;
        $this->urlFinder = $urlFinder;
    }

    /**
     * Override urlrewrite data, basing on current category and product
     *
     * @param \Magento\UrlRewrite\Model\UrlRewrite $model
     * @return void
     * @throws Exception
     */
    protected function _handleCatalogUrlRewrite($model)
    {
        $productId = $this->_getProduct()->getId();
        $categoryId = $this->_getCategory()->getId();
        if ($productId || $categoryId) {
            if ($model->isObjectNew()) {
                $model->setEntityType($productId ? self::ENTITY_TYPE_PRODUCT : self::ENTITY_TYPE_CATEGORY)
                    ->setEntityId($productId ? : $categoryId);
                if ($productId && $categoryId) {
                    $model->setMetadata(serialize(['category_id' => $categoryId]));
                }
            }
            $model->setTargetPath($this->getTargetPath($model));
        }
    }

    /**
     * Get Target Path
     *
     * @param \Magento\UrlRewrite\Model\UrlRewrite $model
     * @return string
     * @throws Exception
     */
    protected function getTargetPath($model)
    {
        $targetPath = $this->getCanonicalTargetPath();
        if ($model->getRedirectType() && !$model->getIsAutogenerated()) {
            $data = [
                UrlRewrite::ENTITY_ID => $model->getEntityId(),
                UrlRewrite::TARGET_PATH => $targetPath,
                UrlRewrite::ENTITY_TYPE => $model->getEntityType(),
                UrlRewrite::STORE_ID => $model->getStoreId(),
            ];
            $rewrite = $this->urlFinder->findOneByData($data);
            if (!$rewrite) {
                $message = $model->getEntityType() === self::ENTITY_TYPE_PRODUCT
                    ? __('Chosen product does not associated with the chosen store or category.')
                    : __('Chosen category does not associated with the chosen store.');
                throw new Exception($message);
            }
            $targetPath = $rewrite->getRequestPath();
        }
        return $targetPath;
    }

    /**
     * @return string
     */
    protected function getCanonicalTargetPath()
    {
        $product = $this->_getProduct()->getId() ? $this->_getProduct() : null;
        $category = $this->_getCategory()->getId() ? $this->_getCategory() : null;
        return $product
            ? $this->productUrlPathGenerator->getCanonicalUrlPath($product, $category)
            : $this->categoryUrlPathGenerator->getCanonicalUrlPath($category);
    }

    /**
     * Override URL rewrite data, basing on current CMS page
     *
     * @param \Magento\UrlRewrite\Model\UrlRewrite $model
     * @return void
     */
    private function _handleCmsPageUrlRewrite($model)
    {
        $cmsPage = $this->_getCmsPage();
        if ($cmsPage->getId()) {
            if ($model->isObjectNew()) {
                $model->setEntityType(self::ENTITY_TYPE_CMS_PAGE)->setEntityId($cmsPage->getId());
            }
            if ($model->getRedirectType() && !$model->getIsAutogenerated()) {
                $targetPath = $this->cmsPageUrlPathGenerator->getUrlPath($cmsPage);
            } else {
                $targetPath = $this->cmsPageUrlPathGenerator->getCanonicalUrlPath($cmsPage);
            }
            $model->setTargetPath($targetPath);
        }
    }

    /**
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            /** @var $session \Magento\Backend\Model\Session */
            $session = $this->_objectManager->get('Magento\Backend\Model\Session');
            try {
                $model = $this->_getUrlRewrite();

                $requestPath = $this->getRequest()->getParam('request_path');
                $this->_objectManager->get('Magento\UrlRewrite\Helper\UrlRewrite')->validateRequestPath($requestPath);

                $model->setEntityType($this->getRequest()->getParam('entity_type', self::ENTITY_TYPE_CUSTOM))
                    ->setRequestPath($requestPath)
                    ->setTargetPath($this->getRequest()->getParam('target_path', $model->getTargetPath()))
                    ->setRedirectType($this->getRequest()->getParam('redirect_type'))
                    ->setStoreId($this->getRequest()->getParam('store_id', 0))
                    ->setDescription($this->getRequest()->getParam('description'));

                $this->_handleCatalogUrlRewrite($model);
                $this->_handleCmsPageUrlRewrite($model);
                $model->save();

                $this->messageManager->addSuccess(__('The URL Rewrite has been saved.'));
                $this->_redirect('adminhtml/*/');
                return;
            } catch (Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $session->setUrlRewriteData($data);
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('An error occurred while saving URL Rewrite.'));
                $session->setUrlRewriteData($data);
            }
        }
        $this->getResponse()->setRedirect($this->_redirect->getRedirectUrl($this->getUrl('*')));
    }
}
