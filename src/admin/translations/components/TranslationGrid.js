import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Save, ChevronDown, ChevronRight, Search } from 'lucide-react';
import Highlight from './Highlight';
import EditableCell from './EditableCell';

/**
 * Reusable translation grid — shows grouped strings with per-language inputs.
 * Used by both Theme Strings and Blocks tabs.
 */
export default function TranslationGrid( { dataKey } ) {
    const languages = window.snelTranslations?.languages || [];
    const defaultLang = window.snelTranslations?.defaultLang || 'nl';
    const nonDefaultLangs = languages.filter( ( l ) => ! l.default );

    const [ grouped, setGrouped ] = useState( () => window.snelTranslations?.[ dataKey ] || {} );
    const [ saving, setSaving ] = useState( false );
    const [ notice, setNotice ] = useState( null );
    const [ collapsed, setCollapsed ] = useState( {} );
    const [ searchQuery, setSearchQuery ] = useState( '' );

    const toggleSection = ( section ) => {
        setCollapsed( ( prev ) => ( { ...prev, [ section ]: ! prev[ section ] } ) );
    };

    const updateTranslation = ( dutchKey, lang, value ) => {
        setGrouped( ( prev ) => {
            const next = { ...prev };
            for ( const section in next ) {
                if ( dutchKey in next[ section ] ) {
                    next[ section ] = {
                        ...next[ section ],
                        [ dutchKey ]: {
                            ...next[ section ][ dutchKey ],
                            [ lang ]: value,
                        },
                    };
                    break;
                }
            }
            return next;
        } );
    };

    const handleSave = async () => {
        setSaving( true );
        setNotice( null );

        const flat = {};
        for ( const section in grouped ) {
            for ( const dutchKey in grouped[ section ] ) {
                flat[ dutchKey ] = grouped[ section ][ dutchKey ];
            }
        }

        try {
            const res = await fetch( `${ window.snelTranslations.restUrl }/theme-strings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.snelTranslations.nonce,
                },
                body: JSON.stringify( flat ),
            } );

            setNotice( res.ok
                ? { type: 'success', message: __( 'Translations saved.', 'snel' ) }
                : { type: 'error', message: __( 'Failed to save translations.', 'snel' ) }
            );
        } catch {
            setNotice( { type: 'error', message: __( 'Network error.', 'snel' ) } );
        }

        setSaving( false );
    };

    // Filter grouped data by search query (matches Dutch key or any translation).
    const query = searchQuery.toLowerCase().trim();
    const filteredGrouped = {};
    for ( const section in grouped ) {
        const filtered = {};
        for ( const dutchKey in grouped[ section ] ) {
            if ( ! query ) {
                filtered[ dutchKey ] = grouped[ section ][ dutchKey ];
                continue;
            }
            const langs = grouped[ section ][ dutchKey ];
            const matches = dutchKey.toLowerCase().includes( query )
                || Object.values( langs ).some( ( v ) => v && v.toLowerCase().includes( query ) )
                || section.toLowerCase().includes( query );
            if ( matches ) {
                filtered[ dutchKey ] = langs;
            }
        }
        if ( Object.keys( filtered ).length > 0 ) {
            filteredGrouped[ section ] = filtered;
        }
    }

    const sections = Object.keys( filteredGrouped );
    const totalStrings = sections.reduce( ( sum, s ) => sum + Object.keys( filteredGrouped[ s ] ).length, 0 );
    const missingCount = sections.reduce( ( sum, section ) => {
        return sum + Object.keys( filteredGrouped[ section ] ).reduce( ( sSum, key ) => {
            const langs = filteredGrouped[ section ][ key ];
            return sSum + nonDefaultLangs.filter( ( l ) => ! langs[ l.code ] ).length;
        }, 0 );
    }, 0 );

    return (
        <div>
            <div className="flex items-center justify-between mb-4">
                <div className="flex items-center gap-3">
                    <span className="text-sm text-gray-500">
                        { totalStrings } { __( 'strings', 'snel' ) }
                    </span>
                    { missingCount > 0 && (
                        <span className="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">
                            { missingCount } { __( 'missing', 'snel' ) }
                        </span>
                    ) }
                </div>
                <div className="flex items-center gap-3">
                    <div className="relative">
                        <Search size={ 14 } className="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input
                            type="text"
                            value={ searchQuery }
                            onChange={ ( e ) => setSearchQuery( e.target.value ) }
                            placeholder={ __( 'Search translations...', 'snel' ) }
                            className="pl-8 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 focus:shadow-[0_0_0_1px_#3b82f6] w-56"
                        />
                    </div>
                <button
                    onClick={ handleSave }
                    disabled={ saving }
                    className="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                >
                    <Save size={ 16 } />
                    { saving ? __( 'Saving...', 'snel' ) : __( 'Save Translations', 'snel' ) }
                </button>
                </div>
            </div>

            { notice && (
                <div className={ `mb-4 px-4 py-3 rounded-lg text-sm ${ notice.type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }` }>
                    { notice.message }
                    <button onClick={ () => setNotice( null ) } className="float-right font-bold">×</button>
                </div>
            ) }

            { totalStrings === 0 && (
                <div className="bg-white border border-gray-200 rounded-lg p-6 text-center text-sm text-gray-400 py-12">
                    { query ? __( 'No results found.', 'snel' ) : __( 'No strings found.', 'snel' ) }
                </div>
            ) }

            <div className="space-y-3">
                { sections.map( ( section ) => {
                    const strings = filteredGrouped[ section ];
                    const keys = Object.keys( strings );
                    const isCollapsed = collapsed[ section ];
                    const sectionMissing = keys.reduce( ( sum, key ) => {
                        return sum + nonDefaultLangs.filter( ( l ) => ! strings[ key ][ l.code ] ).length;
                    }, 0 );

                    return (
                        <div key={ section } className="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <button
                                onClick={ () => toggleSection( section ) }
                                className="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors text-left"
                            >
                                <div className="flex items-center gap-2">
                                    { isCollapsed
                                        ? <ChevronRight size={ 16 } className="text-gray-400" />
                                        : <ChevronDown size={ 16 } className="text-gray-400" />
                                    }
                                    <span className="text-sm font-semibold text-gray-700"><Highlight text={ section } query={ query } /></span>
                                    <span className="text-xs text-gray-400">({ keys.length })</span>
                                </div>
                                { sectionMissing > 0 && (
                                    <span className="px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">
                                        { sectionMissing } { __( 'missing', 'snel' ) }
                                    </span>
                                ) }
                            </button>

                            { ! isCollapsed && (
                                <div className="divide-y divide-gray-100">
                                    <div className="grid px-4 py-2 bg-gray-50/50 text-xs font-medium text-gray-400 uppercase tracking-wider" style={ { gridTemplateColumns: `1fr ${ nonDefaultLangs.map( () => '1fr' ).join( ' ' ) }` } }>
                                        <div>{ defaultLang.toUpperCase() } ({ __( 'source', 'snel' ) })</div>
                                        { nonDefaultLangs.map( ( l ) => (
                                            <div key={ l.code }>{ l.label }</div>
                                        ) ) }
                                    </div>

                                    { keys.map( ( dutchKey ) => {
                                        const langs = strings[ dutchKey ];
                                        return (
                                            <div
                                                key={ dutchKey }
                                                className="grid px-4 py-2.5 gap-3 items-start"
                                                style={ { gridTemplateColumns: `1fr ${ nonDefaultLangs.map( () => '1fr' ).join( ' ' ) }` } }
                                            >
                                                <div className="text-sm text-gray-700 pt-1.5 font-medium break-words">
                                                    <Highlight text={ dutchKey } query={ query } />
                                                </div>

                                                { nonDefaultLangs.map( ( l ) => (
                                                    <EditableCell
                                                        key={ l.code }
                                                        value={ langs[ l.code ] || '' }
                                                        onChange={ ( v ) => updateTranslation( dutchKey, l.code, v ) }
                                                        placeholder={ dutchKey }
                                                        query={ query }
                                                        missing={ ! langs[ l.code ] }
                                                    />
                                                ) ) }
                                            </div>
                                        );
                                    } ) }
                                </div>
                            ) }
                        </div>
                    );
                } ) }
            </div>
        </div>
    );
}
