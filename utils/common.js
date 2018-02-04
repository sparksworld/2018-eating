function showLoading(status) {
	if(status) {
		wx.showLoading({
<<<<<<< HEAD
	        title: '加载中',
=======
	        title: '努力加载中...',
>>>>>>> master
	    })
	    return
	}
	if(!status) {
        wx.hideLoading()
	}   
}
module.exports = {
	showLoading: showLoading
}