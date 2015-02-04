(function() {
'use strict';
var app = angular.module('editorialCommentsApp', ['ngActivityIndicator', 'angularMoment', 'infinite-scroll'])
  .config(function($interpolateProvider, $sceProvider, $sceDelegateProvider) {
      $interpolateProvider.startSymbol('{[{').endSymbol('}]}');
});

/**
* A factory which is responsible to load comments using ngInfinite
*
* @class Comments
*/
app.factory('Comments', function($http, $activityIndicator) {
  	var Comments = function() {
	    this.items = [];
	    this.busy = false;
	    this.after = 1;
	    this.itemsCount = 1;
	    this.articleNumber = null;
	    this.articleLanguage = null;
	};

	Comments.prototype.getOne = function(url) {
        return $http({
            method: "GET",
            url: url
        });
	}

	Comments.prototype.create = function(formData) {
        return $http({
            method: "POST",
            url: Routing.generate("newscoop_gimme_articles_create_editorial_comment", {
            	language: this.articleLanguage,
	            number: this.articleNumber
            }),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            data: $.param(formData)
        });
	}

	Comments.prototype.delete = function(commentId) {
        return $http({
            method: "DELETE",
            url: Routing.generate("newscoop_gimme_articles_remove_editorial_comment", {
            	language: this.articleLanguage,
	            number: this.articleNumber,
	            commentId: commentId
            })
        });
	}

	Comments.prototype.update = function(formData, commentId) {
        return $http({
            method: "POST",
            url: Routing.generate("newscoop_gimme_articles_edit_editorial_comment", {
            	language: this.articleLanguage,
	            number: this.articleNumber,
	            commentId: commentId
            }),
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            data: $.param(formData)
        });
	}

	Comments.prototype.refresh = function() {

	    var url = Routing.generate("newscoop_gimme_articles_get_editorial_comments", {
            language: this.articleLanguage,
            number: this.articleNumber,
            order: 'nested',
        });

	    if (this.itemsCount <= 5) {
	    	if (this.busy) return;
	    	this.busy = true;
		    $http.get(url).success(function (data) {
		      	this.itemsCount = data.items.length;
		      	if (this.itemsCount > 0) {
		      		// TODO add items which dont exist in local array and exist in response
		      		// same for removing
		      		this.items = data.items;
		      	}

		      this.busy = false;
		      $activityIndicator.stopAnimating();
		    }.bind(this));
		}
  	}

  	Comments.prototype.nextPage = function(articleNumber, articleLanguage) {
  		if (!this.articleNumber) {
  			this.articleNumber = articleNumber;
  		}

  		if (!this.articleLanguage) {
  			this.articleLanguage = articleLanguage;
  		}

	    if (this.busy) return;
	    this.busy = true;

	    var url = Routing.generate("newscoop_gimme_articles_get_editorial_comments", {
		    language: articleLanguage,
		    number: articleNumber,
		    order: 'nested'
		});

	    if (!$http.defaults.headers.common.Authorization) {
		    $http.get(Routing.generate("newscoop_gimme_users_getuseraccesstoken", {
	            clientId: clientId
		    })).success(function(data, status, headers, config) {
				$http.defaults.headers.common.Authorization = 'Bearer ' + data.access_token;
		  		$http.get(url).success(function (data) {
				    this.items = data.items;
				    this.busy = false;
				}.bind(this));
			}.bind(this));
		} else {
		  	$http.get(url).success(function (data) {
				this.items = data.items;
				this.busy = false;
			}.bind(this));
		}
  	};

  	return Comments;
});


/**
* AngularJS controller for managing various actions on the editorial comments, e.g.
* adding new comments, resolving comments etc.
*
* @class EditorialCommentsCtrl
*/
app.controller('EditorialCommentsCtrl', [
	'$scope',
	'$activityIndicator',
	'$timeout',
	'Comments',
	'$interval',
	function (
		$scope,
		$activityIndicator,
		$timeout,
		Comments,
		$interval
	) {

	var comments = new Comments();
	$scope.comments = comments;

	$interval(function(){
		comments.refresh();
    }.bind(this), 20000);


	/**
     * Updates comments array. It adds a new comment to the array
     * of the comments.
     *
     * @param  {array}   comments   Array of the comments data
     * @param  {integer} parentId   Parent comment id
     * @param  {object}  newComment Newly inserted comment object
     */
    var addChildComment = function (comments, parentId, newComment) {
    	var index = 0;
        for (var i = 0; i < comments.length; i++) {
            if (comments[i].parent && comments[i].parent.id == parentId) {
            	index = comments.indexOf(comments[i]);
            }
        }

        index = index + 1;
        comments.splice(index, 0, newComment);
    };

    /**
     * Removes comment and its children from the array of not solved comments
     *
     * @param  {array}   comments Array of comments
     * @param  {integer} id       comment id
     */
    var removeCommentWithChildrenFromArray = function (comments, id) {
        for (var i = 0; i < comments.length; i++) {
            if (comments[i].id == id || (comments[i].parent && comments[i].parent.id == id)) {
                comments.splice(comments.indexOf(comments[i]), 1);
            }
        }
    };

	/**
     * Hides/shows replying box
     *
     * @method showReplyBox
     * @param scope {object} currently selected element
     */
    $scope.showReplyBox = function(scope) {
      if (scope.isReplying) {
        scope.isReplying = false;
      } else {
        scope.isReplying = true;
      }
    };

    /**
     * Hides/shows edit box
     *
     * @method isEditing
     * @param scope {object} currently selected element
     */
    $scope.isEditing = function(scope) {
      if (scope.editing) {
        scope.editing = false;
      } else {
        scope.editing = true;
      }
    };

    /**
     * Hide button, hiding extra options like e.g. adding new substopic etc.
     *
     * @method hideExtraOptions
     * @parent scope {object} currently selected element in a tree
     */
    $scope.hide = function(scope) {
      scope.editing = false;
      scope.isReplying = false;
    };

    /**
     * Resolves editorial comment
     *
     * @method resolveComment
     * @param commentId {integer} comment's id
     */
    $scope.resolveComment = function(commentId) {
    	var postData = {
            editorial_comment: {
                resolved: true,
            },
            _csrf_token: token
        };

      	comments.update(postData, commentId).success(function (data) {
	        flashMessage(Translator.trans('editorial.alert.resolved', {}, 'comments'));
	        removeCommentWithChildrenFromArray($scope.comments.items, commentId);
	    }).error(function(data, status){
	        flashMessage(data.errors[0].message, 'error');
	    });
    };

    /**
     * Updates comment
     *
     * @method editComment
     * @param comment {object} comment object
     */
    $scope.editComment = function(comment) {
       var postData = {
          editorial_comment: {
              comment: comment.comment,
          },
          _csrf_token: token
      };

      comments.update(postData, comment.id).success(function (data) {
	        flashMessage(Translator.trans('editorial.alert.edited', {}, 'comments'));
			comment.editing = false;
	    }).error(function(data, status){
	        flashMessage(data.errors[0].message, 'error');
	    });

    };

    /**
     * Deletes comment
     *
     * @method deleteComment
     * @param comment {integer} comment's id
     */
    $scope.deleteComment = function(commentId) {
      comments.delete(commentId).success(function (data) {
	        flashMessage(Translator.trans('editorial.alert.deleted', {}, 'comments'));
	        removeCommentWithChildrenFromArray($scope.comments.items, commentId);
	    }).error(function(data, status){
	        flashMessage(data.errors[0].message, 'error');
	    });
    };

    $scope.textareaMessage = {};
    $scope.textareaReply = {};

    /**
     * Resolves editorial comment
     *
     * @method addComment
     * @param comment {integer} comment
     */
    $scope.addComment = function(comment) {
        var addFormData = {
            editorial_comment: {},
            _csrf_token: token
        }

        addFormData.editorial_comment["comment"] = $scope.textareaMessage.comment;

        if (comment.id && $scope.textareaReply.comment) {
        	addFormData.editorial_comment["comment"] = $scope.textareaReply.comment;
        	addFormData.editorial_comment["parent"] = comment.id;
        }

      	comments.create(addFormData).success(function (data, code, headers) {
	        comments.getOne(headers('X-Location')).success(function (data) {
	        	if (addFormData.editorial_comment.parent) {
	        		addChildComment(comments.items, addFormData.editorial_comment.parent, data);
	        	} else {
	        		comments.items.push(data);
	        	}
	        	flashMessage(Translator.trans('editorial.alert.added', {}, 'comments'));
	        	$scope.textareaMessage = {};
	        	$scope.textareaReply = {};
	        	comment.isReplying = false;
	        }).error(function(data, status){
		        flashMessage(data.errors[0].message, 'error');
		    });
	    }).error(function(data, status){
	        flashMessage(data.errors[0].message, 'error');
	    });
    };
}]);

})();