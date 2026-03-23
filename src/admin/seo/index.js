import { createRoot } from '@wordpress/element';
import { Panel, PanelBody, PanelRow, Card, CardBody } from '@wordpress/components';

const SeoApp = () => {
    return (
        <div>
            <h1>SEO</h1>
            <Panel>
                <PanelBody title="Overview" initialOpen={true}>
                    <PanelRow>
                        <Card>
                            <CardBody>
                                Hello world — this is the SEO module.
                            </CardBody>
                        </Card>
                    </PanelRow>
                </PanelBody>
            </Panel>
        </div>
    );
};

document.addEventListener( 'DOMContentLoaded', () => {
    const root = document.getElementById( 'snel-seo-root' );
    if ( root ) {
        createRoot( root ).render( <SeoApp /> );
    }
} );
