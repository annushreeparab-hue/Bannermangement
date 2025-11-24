import { Box, Skeleton } from "@mui/material";
import BannerSlider from "./BannerSlider";

const Banner = ({ banners = [] }) => {
  // The Skeleton will be rendered on the server if banners are not available
  // or until the client-side BannerClient component loads.
  if (!banners || banners.length === 0) {
    return (
      <Box sx={{ px: { xs: 2, sm: 3, md: 5 }, mt: 2 }}>
        <Skeleton variant="rectangular" animation="wave" width="100%" height={300} />
      </Box>
    );
  }

  return (
    <Box
      sx={{
        mt: 2,
        px: { xs: 2, sm: 3, md: 5 },
        position: "relative",
      }}
    >
      <BannerSlider banners={banners} />
    </Box>
  );
};

export default Banner;