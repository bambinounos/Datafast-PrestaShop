<?php


class datafastErrorModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        // Los temas classic de PS8/PS9 definen {renderLogo} en _partials/helpers.tpl;
        // header.tpl la llama y esta página no pasa por el layout que la carga antes.
        Context::getContext()->smarty->assign(
            'datafast_theme_has_helpers',
            is_file(_PS_THEME_DIR_ . 'templates/_partials/helpers.tpl')
        );
        $error_message = Context::getContext()->cookie->errorMessage;
        Context::getContext()->smarty->assign(array(
            'error_msg' => $error_message,
            'redirect' => $this->context->link->getPageLink('order')
        ));

        $this->setTemplate('module:datafast/views/templates/front/error.tpl');
    }
}
