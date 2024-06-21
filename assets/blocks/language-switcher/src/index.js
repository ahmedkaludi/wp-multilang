/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ }   from '@wordpress/i18n';

// Register the block
registerBlockType( 'wpm/language-switcher', {
	title: "Language Switcher",
	icon: "translation",
	supports: {
              multiple: false
    },
    parent: [ 'core/navigation', 'core/post-content' ],
    attributes: {
        switchType: {
            type: 'string',
            default: 'dropdown',
        },
        switchShow: {
            type: 'string',
            default: 'both',
        }
    },
    edit: function (props) {

    	var attributes = props.attributes;

    	const { attributes: { mySelect }, setAttributes } = props;

   		const typeOptions = [ { label: __('Drop Down', 'wp-multilang'), value: 'dropdown' },
                              { label: __('List', 'wp-multilang'), value: 'list' },
                              { label: __('Select', 'wp-multilang'), value: 'select' },
                        	]; 	

        const showOptions = [ { label: __('Both', 'wp-multilang'), value: 'both' },
                              { label: __('Flag', 'wp-multilang'), value: 'flag' },
                              { label: __('Name', 'wp-multilang'), value: 'name' },
                        	];

        const lang = wpmLanguageSwitcher.lang; 
    	const languages = wpmLanguageSwitcher.languages;

    	const baseFlagUrl =  wpmLanguageSwitcher.flag_url
    	const defaultFlag =  baseFlagUrl + languages[lang].flag;
    	const defaultLang =  languages[lang].name;

    	const currentURL  =  window.location.href;
    	const convertlangArray = Object.keys(languages);


        const renderDropdownSwitcher = () => {
        	let ulClass = "wpm-language-switcher wpm-switcher-dropdown";
        	let liClass = "wpm-item-language-main wpm-item-language-en";

        	return (
    			<ul className={ulClass}>
    				<li className={liClass}>
    					<span>
    						{(attributes.switchShow == "flag" || attributes.switchShow == "both") &&
    							<img src={defaultFlag} alt={defaultLang} />
    						}
    						{(attributes.switchShow == "name" || attributes.switchShow == "both") &&
								<span>{defaultLang}</span>
							}
    					</span> 
    					<ul className="wpm-language-dropdown">
    						{convertlangArray.map((langIndex, langValue) => ( 
    							langIndex != lang && 
        						<li key={langValue} className={'wpm-item-language-'+langIndex}>
									<a href="#" data-lang={langIndex}>
										{ (attributes.switchShow == "flag" || attributes.switchShow == "both") && 
											<img src={baseFlagUrl+languages[langIndex].flag} alt={languages[langIndex].name} />
										}
										{ (attributes.switchShow == "name" || attributes.switchShow == "both") &&
											<span>{languages[langIndex].name}</span>
										}
									</a>
								</li>
    						))}
						</ul>
    				</li>
    			</ul>
        	);
        }

        const renderListSwitcher = () => {
        	return (
        		<ul className="wpm-language-switcher wpm-switcher-list">
        			{convertlangArray.map((langIndex, langValue) => ( 
        				<li key={langValue} className={'wpm-item-language-'+langIndex}>
        					{ lang == langIndex && 
        						<span data-lang={langIndex}>
        						{ (attributes.switchShow == "flag" || attributes.switchShow == "both") && 
									<img src={baseFlagUrl+languages[langIndex].flag} alt={languages[langIndex].name} />
								}
								{ (attributes.switchShow == "name" || attributes.switchShow == "both") &&
									<span>{languages[langIndex].name}</span>
								}
								</span>
        					}

        					{ lang != langIndex && 
        						<a href="#" data-lang={langIndex}>
        						{ (attributes.switchShow == "flag" || attributes.switchShow == "both") && 
									<img src={baseFlagUrl+languages[langIndex].flag} alt={languages[langIndex].name} />
								}
								{ (attributes.switchShow == "name" || attributes.switchShow == "both") &&
									<span>{languages[langIndex].name}</span>
								}
								</a>
        					} 
        				</li>
        			))}
        		</ul>
        	)
        }

        const renderSelectSwitcher = () => {
        	return (
	        	<select className="wpm-language-switcher wpm-switcher-select" title={__( 'Language Switcher', 'wp-multilang' )}>
	        		{convertlangArray.map((langIndex, langValue) => (
	        			lang != langIndex ?
	        				<option key={langValue} value={langIndex} data-lang={langIndex}>{languages[langIndex].name}</option> :
	        				<option key={langValue} value={langIndex} data-lang={langIndex} selected="selected">{languages[langIndex].name}</option> 
	        		))}
	        	</select>
	        )
        }

        const langSwitcher = () => {
        	if(attributes.switchType == 'dropdown'){
        		return renderDropdownSwitcher();
	        }else if(attributes.switchType == 'list'){
	        	return renderListSwitcher();
	        }else if(attributes.switchType == 'select'){
	        	return renderSelectSwitcher();
	        }
	    };

        return (
            <>
	            <InspectorControls>
	            	<PanelBody>
	            		<SelectControl
	                        label={__('Type', 'wp-multilang')}
	                        value={attributes.switchType}
	                        options={typeOptions}
	                        onChange={(value) => setAttributes({ switchType: value })}
	                    />

	                    <SelectControl
	                        label={__('Show', 'wp-multilang')}
	                        value={attributes.switchShow}
	                        options={showOptions}
	                        onChange={(value) => setAttributes({ switchShow: value })}
	                    />
	            	</PanelBody>
	            </InspectorControls>
	            
	            <div>
	            	{langSwitcher()}
	            </div>
            </>
        )
    },
    save: function (props) {

    	var attributes = props.attributes;

   		const typeOptions = [ { label: __('Drop Down', 'wp-multilang'), value: 'dropdown' },
                              { label: __('List', 'wp-multilang'), value: 'list' },
                              { label: __('Select', 'wp-multilang'), value: 'select' },
                        	]; 	

        const showOptions = [ { label: __('Both', 'wp-multilang'), value: 'both' },
                              { label: __('Flag', 'wp-multilang'), value: 'flag' },
                              { label: __('Name', 'wp-multilang'), value: 'name' },
                        	];

        const lang = wpmLanguageSwitcher.lang; 
    	const languages = wpmLanguageSwitcher.languages;

    	const baseFlagUrl =  wpmLanguageSwitcher.flag_url
    	const defaultFlag =  baseFlagUrl + languages[lang].flag;
    	const defaultLang =  languages[lang].name;

    	const currentURL  =  window.location.href;
    	const convertlangArray = Object.keys(languages);


        const renderDropdownSwitcher = () => {
        	let ulClass = "wpm-language-switcher wpm-switcher-dropdown";
        	let liClass = "wpm-item-language-main wpm-item-language-en";

        	return (
    			<ul className={ulClass}>
    				<li className={liClass}>
    					<span>
    						{(attributes.switchShow == "flag" || attributes.switchShow == "both") &&
    							<img src={defaultFlag} alt={defaultLang} />
    						}
    						{(attributes.switchShow == "name" || attributes.switchShow == "both") &&
								<span>{defaultLang}</span>
							}
    					</span> 
    					<ul className="wpm-language-dropdown">
    						{convertlangArray.map((langIndex, langValue) => ( 
    							langIndex != lang && 
        						<li key={langValue} className={'wpm-item-language-'+langIndex}>
									<a href="#" data-lang={langIndex}>
										{ (attributes.switchShow == "flag" || attributes.switchShow == "both") && 
											<img src={baseFlagUrl+languages[langIndex].flag} alt={languages[langIndex].name} />
										}
										{ (attributes.switchShow == "name" || attributes.switchShow == "both") &&
											<span>{languages[langIndex].name}</span>
										}
									</a>
								</li>
    						))}
						</ul>
    				</li>
    			</ul>
        	);
        }

        const renderListSwitcher = () => {
        	return (
        		<ul className="wpm-language-switcher wpm-switcher-list">
        			{convertlangArray.map((langIndex, langValue) => ( 
        				<li key={langValue} className={'wpm-item-language-'+langIndex}>
        					{ lang == langIndex && 
        						<span data-lang={langIndex}>
        						{ (attributes.switchShow == "flag" || attributes.switchShow == "both") && 
									<img src={baseFlagUrl+languages[langIndex].flag} alt={languages[langIndex].name} />
								}
								{ (attributes.switchShow == "name" || attributes.switchShow == "both") &&
									<span>{languages[langIndex].name}</span>
								}
								</span>
        					}

        					{ lang != langIndex && 
        						<a href="#" data-lang={langIndex}>
        						{ (attributes.switchShow == "flag" || attributes.switchShow == "both") && 
									<img src={baseFlagUrl+languages[langIndex].flag} alt={languages[langIndex].name} />
								}
								{ (attributes.switchShow == "name" || attributes.switchShow == "both") &&
									<span>{languages[langIndex].name}</span>
								}
								</a>
        					} 
        				</li>
        			))}
        		</ul>
        	)
        }

        const renderSelectSwitcher = () => {
        	return (
	        	<select className="wpm-language-switcher wpm-switcher-select" title={__( 'Language Switcher', 'wp-multilang' )}>
	        		{convertlangArray.map((langIndex, langValue) => (
	        			lang != langIndex ?
	        				<option key={langValue} value={langIndex} data-lang={langIndex}>{languages[langIndex].name}</option> :
	        				<option key={langValue} value={langIndex} data-lang={langIndex} selected="selected">{languages[langIndex].name}</option> 
	        		))}
	        	</select>
	        )
        }

        const langSwitcher = () => {
        	if(attributes.switchType == 'dropdown'){
        		return renderDropdownSwitcher();
	        }else if(attributes.switchType == 'list'){
	        	return renderListSwitcher();
	        }else if(attributes.switchType == 'select'){
	        	return renderSelectSwitcher();
	        }
	    };

        return (
                <div>
	            	{langSwitcher()}
	            </div>
            )
    },
} );