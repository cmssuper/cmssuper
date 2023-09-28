import Vue from "vue";
import Vuex from "vuex";

Vue.use(Vuex);

export default new Vuex.Store({
	state: {
		axiosLoading: false,
		pageLoading: true,
		common: {},
		_token: null,
		_siteId: null,
	},
	getters: {
		token: (state) => {
			if (!state._token && localStorage.token) {
				state._token = localStorage.token;
			}
			return state._token;
		},
		sites: (state) => {
			return state.common.sites ? state.common.sites : {};
		},
		sitesList: (state) => {
			let sites = state.common.sites ? state.common.sites : [];
			let sitesList = [];
			for (var item in sites) {
				sitesList.push(sites[item]);
			}
			sitesList.sort(function(a, b) {
				if (a.id < b.id) {
					return 1;
				} else {
					return -1;
				}
			});
			return sitesList;
		},
		siteId: (state, getters) => {
			if (getters.sitesList.length > 0) {
				if (!state._siteId) state._siteId = localStorage.siteId;
				if (state._siteId && getters.sites[state._siteId]) {
					return state._siteId;
				} else {
					let siteId = getters.sitesList[0].id;
					localStorage.setItem("siteId", siteId);
					return siteId;
				}
			} else {
				return 0;
			}
		},
	},
	mutations: {
		setAxiosLoading(state, data) {
			state.axiosLoading = data;
		},
		setPageLoading(state, data) {
			state.pageLoading = data;
		},
		setCommon(state, data) {
			state.common = data;
		},
		setToken(state, data) {
			state._token = data;
		},
		setSiteId(state, data) {
			state._siteId = data;
		},
	},
	actions: {
		setSiteId({ commit, getters }, data) {
			if (getters.sites[data]) {
				localStorage.setItem("siteId", data);
				commit("setSiteId", data);
			}
		},
	},
	modules: {},
});
