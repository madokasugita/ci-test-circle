/**
 * 画像を予めロードしておく
 * @param new Array("img1.jpg","img2.jpg","img3.jpg","img4.jpg");
 */
function imgPreLoad(data){
	prImg= new Array();
	for (i=0; i<data.length; i++)
	{
		prImg[i] = new Image();
		prImg[i].src = data[i];
	}
}