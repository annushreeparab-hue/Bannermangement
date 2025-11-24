"use client";

import React from "react";
// import Slider from "react-slick";
import Image from "next/image";
import { Box, Skeleton, Typography } from "@mui/material";
import "slick-carousel/slick/slick.css";
import "slick-carousel/slick/slick-theme.css";
import dynamic from "next/dynamic";

const Slider = dynamic(() => import("react-slick"), {
    ssr: false,
    loading: () => (
        <Box> {/* Added outer Box for padding/margin consistency */}
            <Skeleton variant="rectangular" animation="wave" width="100%" height={300} />
        </Box>
    ),
});

const BannerSlider = ({ banners }) => {
    const settings = {
        dots: true,
        infinite: true,
        speed: 500,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 10000,
        pauseOnHover: true,
        swipe: true,
        // arrows: true,
        vertical: false,
        fade: true,
        cssEase: "linear",
    };

    return (
        <Slider {...settings}>
            {banners?.map((banner, index) => {
                const imageUrl = banner?.imageUrl || "/assets/images/banner.jpg";
                return (
                    <Box
                        key={index}
                        sx={{
                            position: "relative",
                            width: "100%",
                            // height: { xs: 120, sm: 250, md: 340 },
                            aspectRatio: 4 / 1
                        }}
                    >

                        {/* <Box sx={{border:'1px solid black', height:'100%'}}> */}
                        <Image
                            src={imageUrl}
                            // src={'/assets/images/cropped-BannerImage (4).jpg'}
                            alt={banner?.heading || "Banner Image"}
                            fill
                            priority={index === 0}
                            // sizes="(max-width: 768px) 100vw, (max-width: 1200px) 100vw, 1200px"
                            style={{ objectFit: "contain" }}
                        />
                        {/* </Box> */}
                        {(banner?.heading || banner?.subheading) && (
                            <Box
                                sx={{
                                    position: "absolute",
                                    top: 0,
                                    left: 0,
                                    right: 0,
                                    bottom: 0,
                                    display: "flex",
                                    alignItems: "center",
                                    justifyContent: "flex-end",
                                    padding: 3,
                                    paddingTop: { xs: 3, md: 8 },
                                    boxSizing: "border-box",
                                }}
                            >
                                <Box
                                    sx={{
                                        color: `${banner.textColor || "white"}`,
                                        textAlign: "right",
                                        background: "rgba(0,0,0,0.4)",
                                        maxWidth: { xs: "100%", md: "60%" },
                                        zIndex: 1,
                                        padding: 2,
                                        borderRadius: 2,
                                    }}
                                >
                                    {banner?.heading && (
                                        <Typography
                                            variant="h3"
                                            sx={{
                                                fontWeight: 400,
                                                mb: 2,
                                                fontSize: { xs: "1.5rem", md: "2.5rem" },
                                            }}
                                        >
                                            {banner.heading}
                                        </Typography>
                                    )}
                                    {banner?.subheading && (
                                        <Typography
                                            variant="h5"
                                            sx={{
                                                fontWeight: 300,
                                                fontSize: { xs: "1rem", md: "2rem" },
                                                lineHeight: 1.2,
                                            }}
                                        >
                                            {banner.subheading}
                                        </Typography>
                                    )}
                                </Box>
                            </Box>
                        )}
                    </Box>

                    // <Box
                    //     key={index}
                    //     sx={{
                    //         position: "relative",
                    //         width: "100%",
                    //         height: { xs: 120, md: 340 },
                    //         backgroundImage: `url(${imageUrl})`,
                    //         backgroundSize: "cover",
                    //         backgroundPosition: "top left",
                    //         backgroundRepeat: "no-repeat",
                    //         backgroundColor: "transparent",
                    //     }}
                    // >
                    //     {(banner?.heading || banner?.subheading) && (
                    //         <Box
                    //             sx={{
                    //                 position: "absolute",
                    //                 top: 0,
                    //                 left: 0,
                    //                 right: 0,
                    //                 bottom: 0,
                    //                 display: "flex",
                    //                 alignItems: "center",
                    //                 justifyContent: "flex-end",
                    //                 padding: 3,
                    //                 paddingTop: { xs: 3, md: 8 },
                    //                 boxSizing: "border-box",
                    //             }}
                    //         >
                    //             <Box
                    //                 sx={{
                    //                     color: "#fff",
                    //                     textAlign: "right",
                    //                     background: "rgba(0,0,0,0.4)",
                    //                     maxWidth: { xs: "100%", md: "60%" },
                    //                     zIndex: 1,
                    //                     padding: 2,
                    //                     borderRadius: 2,
                    //                 }}
                    //             >
                    //                 {banner?.heading && (
                    //                     <Typography
                    //                         variant="h3"
                    //                         sx={{
                    //                             fontWeight: 400,
                    //                             mb: 2,
                    //                             fontSize: { xs: "1.5rem", md: "2.5rem" },
                    //                         }}
                    //                     >
                    //                         {banner.heading}
                    //                     </Typography>
                    //                 )}
                    //                 {banner?.subheading && (
                    //                     <Typography
                    //                         variant="h5"
                    //                         sx={{
                    //                             fontWeight: 300,
                    //                             fontSize: { xs: "1rem", md: "2rem" },
                    //                             lineHeight: 1.2,
                    //                         }}
                    //                     >
                    //                         {banner.subheading}
                    //                     </Typography>
                    //                 )}
                    //             </Box>
                    //         </Box>
                    //     )}
                    // </Box>

                );
            })}
        </Slider >
    );
};

export default BannerSlider;
