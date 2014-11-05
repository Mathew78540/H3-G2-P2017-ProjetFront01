'use strict';

angular.module('app').factory('session', function ($sessionStorage) {
    return {
        loggedUser: function (value) {
            $sessionStorage.logged = value;
        },

        getLogged: function () {
            return $sessionStorage.logged;
        },

        getUser: function () {
            return $sessionStorage.user;
        },

        setUserCategory: function (categoryName) {
            $sessionStorage.user.category_name = categoryName;
        },

        saveUser: function (userInfo) {
            $sessionStorage.user = {
                name            : userInfo.firstname+' '+userInfo.lastname,
                facebook_id     : userInfo.facebook_id,
                firstname       : userInfo.firstname,
                lastname        : userInfo.lastname,
                picture         : userInfo.picture,
                sex             : userInfo.sex,
                category_name   : 'geek'
            };
        },

        deleteUser: function () {
            delete $sessionStorage.user;
            delete $sessionStorage.logged;
        }
    }
});