import * as React from "react";
import ZTable from "../components/Table";
import config from "../config";

const Home = () => {
  const [records, setRecords] = React.useState([]);
  const [loading, setLoading] = React.useState(false);

  const getRecords = async () => {
    setLoading(true);
    await fetch(`${config.apiUrl}/leads`)
      .then((res) => res.json())
      .then((res) => {
        if (res?.success) setRecords(res?.data);
        else setRecords([]);
        setLoading(false);
      }).catch((err) => {
        console.error(err);
        setLoading(false);
      })
  };

  React.useEffect(() => {
    getRecords();
  }, []);

  return (
    <div className="row">
      <div className="col-sm-12 mt-5">
        <ZTable items={records}/>
        {loading && (
            <span>Loading...</span>
        )}
      </div>
    </div>
  );
};

export default Home;
