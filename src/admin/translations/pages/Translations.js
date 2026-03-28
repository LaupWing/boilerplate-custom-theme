import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import TABS from '../config/tabs';
import Tabs from '../components/Tabs';
import TranslationGrid from '../components/TranslationGrid';
import MenuTab from './MenuTab';
import Pages from './Pages';
import GlobalSearch from '../components/GlobalSearch';

export default function Translations() {
    const [ activeTab, setActiveTab ] = useState( 'theme' );
    const [ globalQuery, setGlobalQuery ] = useState( '' );
    const [ initialPageId, setInitialPageId ] = useState( null );

    const handleNavigate = ( tab, query, item ) => {
        setActiveTab( tab );
        setGlobalQuery( query || '' );
        setInitialPageId( item?.pageId || null );
    };

    const handleTabChange = ( tab ) => {
        setActiveTab( tab );
        setGlobalQuery( '' );
        setInitialPageId( null );
    };

    return (
        <div className="p-6">
            <div className="mb-6 flex items-center justify-between">
                <div>
                    <h1 className="text-xl font-bold text-gray-900">
                        Snel <em className="font-serif font-normal italic">Translations</em>
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">
                        { __( 'Manage all translations for your multilingual site', 'snel' ) }
                    </p>
                </div>
                <GlobalSearch onNavigate={ handleNavigate } />
            </div>

            <Tabs tabs={ TABS } active={ activeTab } onChange={ handleTabChange } />

            { activeTab === 'theme' && <TranslationGrid dataKey="themeStrings" initialSearch={ globalQuery } /> }
            { activeTab === 'pages' && <Pages initialSearch={ globalQuery } initialPageId={ initialPageId } /> }
            { activeTab === 'menu' && <MenuTab initialSearch={ globalQuery } /> }
        </div>
    );
}
