<?php
class Impressum extends Controller
{
    public function showAction()
    {
        // text is in src/view/impressum/show.<language>.phtml, data comes from 'business' in config/site.php
        $this->title = t('footer.imprint');
        $this->localizedView('impressum/show');
    }
}
