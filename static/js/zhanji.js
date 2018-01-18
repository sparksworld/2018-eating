function draw(user) {
    console.log(user);
    var canvas = document.getElementById('tutorial');
    if (canvas.getContext) {
        var ctx = canvas.getContext('2d');
        getBase64Image(user.zhanji_back,function(dataURL){
            var img = new Image();
            img.src = dataURL;
            img.onload = function() {
                ctx.drawImage(img, 0, 0);
                drawShare(canvas, ctx, user);
            }
            
        });
    }
}

function drawShare(canvas, ctx, user) {
    if (user) {
        getBase64Image(user.share_url,function(dataURL){
            console.log(user.share_url);
            var img = new Image();
            img.src = dataURL;
            img.onload = function(){
                ctx.drawImage(img, 220, 570,180,180);
                drawHeadimg(canvas, ctx, user);
            }
        });
    }
}

function drawHeadimg(canvas, ctx, user) {
    if (user) {
        getBase64Image(user.headimgurl,function(dataURL){
            var img = new Image();
            img.src = dataURL;
            img.onload = function(){
                ctx.drawImage(img, 25, 43,70,70);
                ctx.fillStyle = "#000";
                ctx.font = "20px serif";
                ctx.fillText(user.allcount, 220, 72);
                ctx.fillText(user.allmoney , 220, 100);
               /*  ctx.fillText('早起' + user.allcount + '天', 200, 90);
                ctx.fillText('累计奖励' + user.allmoney + '元', 200, 140); */
                var imgSrc = canvas.toDataURL("image/png");
                document.getElementById("zhanjiimg").src = imgSrc;  
                layer.close(window.isDraw);
            }
            
        });
    }
}
function getBase64Image(url,callback) {
    var canvas = document.createElement('canvas'),
        ctx = canvas.getContext('2d'),
        img = new Image;
    img.crossOrigin = 'Anonymous';
    img.onload = function () {
        canvas.height = img.height;
        canvas.width = img.width;
        ctx.drawImage(img, 0, 0);
        var dataURL = canvas.toDataURL('image/png');
        canvas = null;
        callback(dataURL);
    };
    img.src = url;
}    