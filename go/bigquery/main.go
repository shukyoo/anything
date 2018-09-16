package main

// import "github.com/gin-gonic/gin"
import (
	"fmt"
	"time"

	_ "github.com/joho/godotenv/autoload"
)

func main() {

	/*
		router := gin.Default()

		router.POST("/form_post", func(c *gin.Context) {
			message := c.PostForm("message")
			nick := c.DefaultPostForm("nick", "anonymous")

			c.JSON(200, gin.H{
				"status":  "posted",
				"message": message,
				"nick":    nick,
			})
		})
		router.Run(":8000")
	*/
	store := newStore()

	fmt.Println(store.set("test", "11", 10*time.Second))
	val, err := store.get("test")
	fmt.Printf("%s:%s", val, err)

}
