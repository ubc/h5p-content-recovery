import React, { Fragment } from 'react';
import './listing-view.scss';

wp.hooks.addFilter(
	'h5p-listing-view-additional-filters',
	'h5p-content-recovery',
	( children ) => {
		const statusOptions = [
			{
				label: 'Published',
				value: 'publish',
			},
			{
				label: 'Trash',
				value: 'trash',
			},
		];

		return (
			<Fragment>
				{ children }
				<div className="content-status-filter">
					{ statusOptions.map( ( option, index ) => {
						console.log(index);
						return (
							<button
								key={ `status-${ index }` }
								value={ option.value }
								className={ 0 === index ? 'active' : '' }
								onClick={ ( e ) => {
									if (
										e.target.classList.contains( 'active' )
									) {
										return;
									}

									for ( const element of e.target
										.parentElement.children ) {
										element.classList.remove( 'active' );
									}

									e.target.classList.add( 'active' );

									window.h5pTaxonomy.listView.fetchContent();
								} }
							>
								{ option.label }
							</button>
						);
					} ) }
				</div>
			</Fragment>
		);
	}
);

wp.hooks.addFilter(
	'h5p-listing-view-additional-form-data',
	'h5p-content-recovery',
	( formData ) => {
		const activeButton = document.querySelector(
			'.content-status-filter button.active'
		);

		formData.append(
			'trash',
			activeButton && 'trash' === activeButton.value ? '1' : '0'
		);
		return formData;
	}
);

wp.hooks.addFilter(
	'h5p-additional-data-row-actions',
	'h5p-content-recovery',
	( actions, entry ) => {

		const isTrashed = '1' === entry.trashed;
		
		const doDelete = async ( e ) => {
			if (
				// eslint-disable-next-line no-alert
				! confirm(
					`Are you sure you wish to delete this content?`
				)
			) {
				e.preventDefault();
	
				return;
			}

			// eslint-disable-next-line no-undef
			const formData = new FormData();
	
			formData.append( 'action', 'ubc_h5p_delete_content' );
			formData.append( 'content_id', entry.id );
			formData.append(
				'nonce',
				// eslint-disable-next-line no-undef, camelcase
				ubc_h5p_content_recovery_admin.security_nonce
			);
	
			// eslint-disable-next-line no-undef
			let response = await fetch( ajaxurl, {
				method: 'POST',
				body: formData,
			} );
	
			response = await response.json();
			window.h5pTaxonomy.listView.fetchContent();
		};
	
		const doTrash = async ( e, trashAction ) => {
	
			if (
				// eslint-disable-next-line no-alert
				! confirm(
					`Are you sure you wish to ${
						'trash' === trashAction ? 'trash' : 'untrash'
					} this content?`
				)
			) {
				e.preventDefault();
	
				return;
			}
	
			// eslint-disable-next-line no-undef
			const formData = new FormData();
	
			formData.append( 'action', 'ubc_h5p_trash_content' );
			formData.append( 'trash_action', trashAction );
			formData.append( 'content_id', entry.id );
			formData.append(
				'nonce',
				// eslint-disable-next-line no-undef, camelcase
				ubc_h5p_content_recovery_admin.security_nonce
			);
	
			// eslint-disable-next-line no-undef
			let response = await fetch( ajaxurl, {
				method: 'POST',
				body: formData,
			} );
	
			response = await response.json();
	
			if ( response.valid ) {
				window.h5pTaxonomy.listView.fetchContent();
			} else {
				alert('Action failed. Contact system administrator.');
			}
		};

		return (
			<Fragment>
				{ ! isTrashed ? actions : null }
				{ 
				! isTrashed ? <a
					role="button"
					className='caution'
					onClick={ e => {
						doTrash( e, 'trash' );
					} }
				>Move to trash</a> : null
				}
				{ 
				isTrashed ? <a
					role="button"
					onClick={ e => {
						doTrash( e, 'untrash' );
					} }
				>Restore</a> : null
				}
				{ 
				isTrashed ? <a
					className='caution'
					role="button"
					onClick={ doDelete }
				>Delete Permanently</a> : null
				}
				
			</Fragment>
		);
	}
);
