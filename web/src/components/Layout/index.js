import { Outlet} from "react-router-dom";
import ZFooter from "../Footer";
import ZHeader from "../Header";

const Layout = () => {
  return (
    <main>
      <ZHeader />
      <div className="container">
        <Outlet />
      </div>
      {/* <ZFooter /> */}
    </main>
  );
};

export default Layout;
