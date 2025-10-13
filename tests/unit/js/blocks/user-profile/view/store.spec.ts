import { jest, describe, it, expect, beforeEach } from '@jest/globals';
import { store, getServerState } from '@wordpress/interactivity';
import type { Store } from '../../../../../../sources/Blocks/UserProfile/view/store';
import {
	assertAnchorElement,
	assertIsPositiveInt,
} from '../../../../../../sources/Blocks/UserProfile/view/asserts';
import { init } from '../../../../../../sources/Blocks/UserProfile/view/store';

jest.mock( '@wordpress/interactivity', () => ( {
	store: jest.fn(),
	getServerState: jest.fn(),
	withSyncEvent: ( fn: Function ) => fn,
} ) );

jest.mock( '../../../../../../sources/Blocks/UserProfile/view/asserts', () => ( {
	assertAnchorElement: jest.fn(),
	assertIsPositiveInt: jest.fn(),
} ) );

describe( 'Interactivity Store', () => {
	let mockNamespace: string;
	let mockStore: Store;

	beforeEach( () => {
		jest.clearAllMocks();
		jest.mocked( getServerState ).mockReturnValue( {
			page: 1,
			perPage: 5,
			updateReason: 'page-update',
		} );
		jest.mocked( store ).mockImplementation( ( namespace: string, config: any ) => {
			mockNamespace = namespace;
			mockStore = config;
			return { ...config };
		} );
	} );

	describe( 'store', () => {
		it( 'should be initialized with the correct namespace', () => {
			init();
			expect( mockNamespace ).toBe( 'konomiProfilePagination' );
		} );
	} );

	describe( 'updatePagination', () => {
		it( 'should update state.page and updateReason on valid anchor', () => {
			init();
			const e = { preventDefault: jest.fn(), currentTarget: document.createElement( 'a' ) };
			e.currentTarget.href = '/page-3';
			jest.mocked( assertAnchorElement ).mockImplementation( () => {
			} );
			mockStore.actions.updatePagination( e as any );
			expect( e.preventDefault ).toHaveBeenCalled();
			expect( assertAnchorElement ).toHaveBeenCalledWith( e.currentTarget );
			expect( mockStore.state.page ).toBe( 3 );
			expect( mockStore.state.updateReason ).toBe( 'page-update' );
		} );
	} );

	describe( 'updatePaginationByHistory', () => {
		it( 'should update state.page and updateReason from history', () => {
			init();
			const e = { state: { konomi: { page: 2 } } };
			jest.mocked( assertIsPositiveInt ).mockImplementation( () => {
			} );
			mockStore.actions.updatePaginationByHistory( e as any );
			expect( assertIsPositiveInt ).toHaveBeenCalledWith( 2 );
			expect( mockStore.state.page ).toBe( 2 );
			expect( mockStore.state.updateReason ).toBe( 'history-change' );
		} );
		it( 'should set page to 1 if assertIsPositiveInt throws', () => {
			init();
			const e = { state: { konomi: { page: 0 } } };
			jest.mocked( assertIsPositiveInt ).mockImplementation( () => {
				throw new Error( 'fail' );
			} );
			mockStore.actions.updatePaginationByHistory( e as any );
			expect( mockStore.state.page ).toBe( 1 );
			expect( mockStore.state.updateReason ).toBe( 'history-change' );
		} );
		it( 'should do nothing if konomi is null', () => {
			init();
			const e = { state: { konomi: null } };
			mockStore.state.page = 5;
			mockStore.actions.updatePaginationByHistory( e as any );
			expect( mockStore.state.page ).toBe( 5 );
		} );
	} );

	describe( 'updateTableRows', () => {
		it( 'should set aria-hidden on rows based on page and perPage', () => {
			init();
			document.body.innerHTML = `
      <figure>
        <table class="has-fixed-layout">
          <tbody>
            <tr class="konomi-user-profile-item"><td class="konomi-user-profile-item__title"><a href="#">Title 1</a></td><td class="konomi-user-profile-item__excerpt">Excerpt 1</td><td class="konomi-user-profile-item__actions has-text-align-center"></td></tr>
            <tr class="konomi-user-profile-item"><td class="konomi-user-profile-item__title"><a href="#">Title 2</a></td><td class="konomi-user-profile-item__excerpt">Excerpt 2</td><td class="konomi-user-profile-item__actions has-text-align-center"></td></tr>
            <tr class="konomi-user-profile-item"><td class="konomi-user-profile-item__title"><a href="#">Title 3</a></td><td class="konomi-user-profile-item__excerpt">Excerpt 3</td><td class="konomi-user-profile-item__actions has-text-align-center"></td></tr>
            <tr class="konomi-user-profile-item"><td class="konomi-user-profile-item__title"><a href="#">Title 4</a></td><td class="konomi-user-profile-item__excerpt">Excerpt 4</td><td class="konomi-user-profile-item__actions has-text-align-center"></td></tr>
            <tr class="konomi-user-profile-item"><td class="konomi-user-profile-item__title"><a href="#">Title 5</a></td><td class="konomi-user-profile-item__excerpt">Excerpt 5</td><td class="konomi-user-profile-item__actions has-text-align-center"></td></tr>
            <tr class="konomi-user-profile-item"><td class="konomi-user-profile-item__title"><a href="#">Title 6</a></td><td class="konomi-user-profile-item__excerpt">Excerpt 6</td><td class="konomi-user-profile-item__actions has-text-align-center"></td></tr>
          </tbody>
        </table>
      </figure>
    `;
			mockStore.state.page = 2;
			mockStore.state.perPage = 2;
			mockStore.callbacks.updateTableRows();
			const rows = document.querySelectorAll( '.konomi-user-profile-item' );
			expect( rows[ 0 ]!.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			expect( rows[ 1 ]!.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			expect( rows[ 2 ]!.getAttribute( 'aria-hidden' ) ).toBe( 'false' );
			expect( rows[ 3 ]!.getAttribute( 'aria-hidden' ) ).toBe( 'false' );
			expect( rows[ 4 ]!.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
			expect( rows[ 5 ]!.getAttribute( 'aria-hidden' ) ).toBe( 'true' );
		} );
		it( 'should do nothing if no rows found', () => {
			init();
			document.body.innerHTML = '';
			expect( () => mockStore.callbacks.updateTableRows() ).not.toThrow();
		} );
	} );

	describe( 'updatePaginationLinks', () => {
		it( 'should update aria-disabled and aria-current on pagination links', () => {
			init();
			document.body.innerHTML = `
      <div class="konomi-user-profile-pagination">
        <a href="/page-1"></a>
        <a href="/page-2"></a>
        <a href="/page-3"></a>
      </div>
    `;
			mockStore.state.page = 2;
			mockStore.callbacks.updatePaginationLinks();
			const links = document.querySelectorAll( '.konomi-user-profile-pagination a' );
			expect( assertAnchorElement ).toHaveBeenCalledTimes( 3 );
			expect( links[ 0 ]!.getAttribute( 'aria-disabled' ) ).toBe( 'false' );
			expect( links[ 1 ]!.getAttribute( 'aria-disabled' ) ).toBe( 'true' );
			expect( links[ 2 ]!.getAttribute( 'aria-disabled' ) ).toBe( 'false' );
		} );
		it( 'should do nothing if no pagination links found', () => {
			init();
			document.body.innerHTML = '<div class="konomi-user-profile-pagination"></div>';
			expect( () => mockStore.callbacks.updatePaginationLinks() ).not.toThrow();
		} );
	} );

	describe( 'updateHistory', () => {
		it( 'should push state to history if updateReason is UPDATE_CURRENT_PAGE', () => {
			init();
			mockStore.state.page = 4;
			mockStore.state.updateReason = 'page-update';
			const pushStateSpy = jest.spyOn( window.history, 'pushState' );
			mockStore.callbacks.updateHistory();
			expect( pushStateSpy ).toHaveBeenCalledWith(
				{ konomi: { page: 4 } },
				'',
				expect.objectContaining( {
					href: expect.stringMatching( /user-profile-page=4/ ),
				} ),
			);
			pushStateSpy.mockRestore();
		} );
		it( 'should do nothing if updateReason is not UPDATE_CURRENT_PAGE', () => {
			init();
			mockStore.state.updateReason = 'history-change';
			const pushStateSpy = jest.spyOn( window.history, 'pushState' );
			mockStore.callbacks.updateHistory();
			expect( pushStateSpy ).not.toHaveBeenCalled();
			pushStateSpy.mockRestore();
		} );
	} );
} );
