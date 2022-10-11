/* eslint-disable camelcase */
/* eslint-disable no-undef */
import React, { Fragment, useState } from 'react';
import ReactDOM from 'react-dom';

import './content-edit-view.scss';

const deleteLink = document.querySelector(
	'#major-publishing-actions .submitdelete'
).href;

const ActionButtons = () => {
	const [ buttonsDisabled, setButtonsDisabled ] = useState( false );

	const doDelete = ( e ) => {
		setButtonsDisabled( true );

		if (
			// eslint-disable-next-line no-alert
			! confirm(
				'Are you sure you wish to Permanently delete this content?'
			)
		) {
			e.preventDefault();
			setButtonsDisabled( false );

			return;
		}

		window.location.href = deleteLink;
	};

	const doTrash = async ( e, trashAction ) => {
		setButtonsDisabled( true );

		if (
			// eslint-disable-next-line no-alert
			! confirm(
				`Are you sure you wish to ${
					'trash' === trashAction ? 'trash' : 'untrash'
				} this content?`
			)
		) {
			e.preventDefault();
			setButtonsDisabled( false );

			return;
		}

		// eslint-disable-next-line no-undef
		const formData = new FormData();
		const params = new URLSearchParams( window.location.search );

		formData.append( 'action', 'ubc_h5p_trash_content' );
		formData.append( 'trash_action', trashAction );
		formData.append(
			'nonce',
			// eslint-disable-next-line no-undef, camelcase
			ubc_h5p_content_recovery_admin.security_nonce
		);

		if ( ! params.has( 'id' ) ) {
			return;
		}

		formData.append( 'content_id', params.get( 'id' ) );

		// eslint-disable-next-line no-undef
		let response = await fetch( ajaxurl, {
			method: 'POST',
			body: formData,
		} );

		response = await response.json();
		// eslint-disable-next-line no-alert
		alert( response.message );

		if ( response.valid ) {
			window.location.reload();
		} else {
			setButtonsDisabled( true );
		}
	};

	return (
		<Fragment>
			<div>
				{ ! ubc_h5p_content_recovery_admin.data.is_content_trashed ? (
					<button
						className="action-button caution"
						onClick={ ( e ) => {
							doTrash( e, 'trash' );
						} }
						disabled={ buttonsDisabled }
					>
						Move to trash
					</button>
				) : null }

				{ ubc_h5p_content_recovery_admin.data.is_content_trashed ? (
					<button
						className="action-button"
						onClick={ ( e ) => {
							doTrash( e, 'untrash' );
						} }
						disabled={ buttonsDisabled }
					>
						Restore
					</button>
				) : null }

				{ ubc_h5p_content_recovery_admin.data.is_content_trashed ? (
					<button
						className="action-button caution"
						onClick={ doDelete }
						disabled={ buttonsDisabled }
					>
						Delete Permanently
					</button>
				) : null }
			</div>

			{ ! ubc_h5p_content_recovery_admin.data.is_content_trashed ? (
				<input
					type="submit"
					name="submit-button"
					value="Update"
					className="button button-primary button-large"
					disabled={ buttonsDisabled }
				></input>
			) : null }
		</Fragment>
	);
};

ReactDOM.render(
	<ActionButtons />,
	// eslint-disable-next-line no-undef
	document.getElementById( 'major-publishing-actions' )
);